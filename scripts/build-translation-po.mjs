/**
 * Pliki .po z polskim tlumaczeniem wtyczki do importu na translate.wordpress.org.
 *
 * Pobiera z translate.wordpress.org aktualne oryginaly czterech projektow wtyczki
 * (stable, dev, stable-readme, dev-readme) i wypelnia je polskimi tekstami z repo:
 *   - interfejs z languages/cookiezen-pl_PL.po;
 *   - opis z docs/readme-pl.txt, parowany jednostka w jednostke z readme.txt.
 * Pliki trafiaja do katalogu wyjsciowego (domyslnie dist/tlumaczenia/, poza gitem),
 * a import robi czlowiek w GlotPress ("Import Translations", status Current), bo
 * wymaga konta z rola PTE. Procedura: PUBLISHING.md, sekcja "Tlumaczenie po wydaniu".
 *
 * Uzycie:
 *   node scripts/build-translation-po.mjs [katalog-wyjsciowy]
 *
 * Skrypt nie zapisuje niczego, gdy struktura readme.txt i docs/readme-pl.txt sie
 * rozjechala (pary bylyby przesuniete, a import wgralby je jako zatwierdzone) albo
 * gdy ktorys eksport nie jest plikiem .po. Kod wyjscia 1 daja tez: oryginal bez
 * tlumaczenia (plik powstaje, a puste pozycje GlotPress pomija), tlumaczenie
 * identyczne z oryginalem i polskie pary readme, ktorych eksport nie zawiera, czyli
 * przestarzaly projekt readme albo fragment Markdown, ktorego render() nie odtwarza.
 */
import { mkdirSync, readFileSync, writeFileSync } from 'node:fs'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..')
const OUT = process.argv[2] || join(ROOT, 'dist', 'tlumaczenia')
const BASE = 'https://translate.wordpress.org/projects/wp-plugins/cookiezen'

// Sekcje readme, ktorych tytul nie jest tekstem do tlumaczenia (katalog ma dla nich wlasne etykiety).
const SKIP_SECTIONS = new Set(['Description', 'Installation', 'Frequently Asked Questions', 'Changelog', 'Upgrade Notice', 'Screenshots'])
// Teksty, ktorych docs/readme-pl.txt nie tlumaczy: nazwa marki i tytul sekcji pisany w obu plikach po angielsku.
const README_FIXED = { CookieZen: 'CookieZen', 'External services': 'Usługi zewnętrzne' }
// Naglowek wtyczki: nazwa, autor i adresy zostaja bez zmian.
const UI_UNCHANGED = new Set(['CookieZen', 'Semavo Solutions Sp. z o.o.'])
// Teksty, ktore swiadomie zostaja takie same po polsku; kazdy inny identyczny tekst to brak tlumaczenia.
const isIntentionallySame = (id) => UI_UNCHANGED.has(id) || id.startsWith('https://cookiezen.pl') || /^\d+(\.\d+)+$/.test(id)

const unquote = (lines) => lines.map((l) => JSON.parse(l.slice(l.indexOf('"')))).join('')
const quote = (s) => '"' + s.replace(/\\/g, '\\\\').replace(/"/g, '\\"').replace(/\n/g, '\\n').replace(/\t/g, '\\t').replace(/\r/g, '\\r') + '"'

/** Wpisy pliku .po w kolejnosci: surowe linie, msgid i msgstr. */
function parsePo(text) {
  return text.trim().split(/\n\s*\n/).map((block) => {
    const lines = block.split('\n')
    const idAt = lines.findIndex((l) => l.startsWith('msgid '))
    const strAt = lines.findIndex((l) => l.startsWith('msgstr'))
    if (idAt < 0 || strAt < 0) return { lines, msgid: null }
    const plural = lines.some((l) => l.startsWith('msgid_plural '))
    return { lines, strAt, plural, msgid: unquote(lines.slice(idAt, strAt).filter((l) => !l.startsWith('msgid_plural'))), msgstr: plural ? '' : unquote(lines.slice(strAt)) }
  })
}

function fill(exportText, translate) {
  const missing = []
  const same = []
  const used = new Set()
  const blocks = parsePo(exportText).map((e) => {
    if (e.msgid === null || e.msgid === '') return e.lines.join('\n')
    // Liczby mnogiej plugin dzis nie ma; taki wpis zostaje do recznego tlumaczenia.
    if (e.plural) { missing.push(e.msgid); return e.lines.join('\n') }
    const tr = translate(e.msgid)
    if (!tr) missing.push(e.msgid)
    else { used.add(e.msgid); if (tr === e.msgid && !isIntentionallySame(e.msgid)) same.push(e.msgid) }
    // Flaga fuzzy z eksportu oznaczylaby wypelnione tlumaczenie jako niepewne.
    const head = e.lines.slice(0, e.strAt).map((l) => (l.startsWith('#,') ? l.replace(/,\s*fuzzy/, '') : l)).filter((l) => l.trim() !== '#,')
    return [...head, 'msgstr ' + quote(tr || '')].join('\n')
  })
  return { text: blocks.join('\n\n') + '\n', missing, same, used }
}

/** Jednostki readme w kolejnosci dokumentu: krotki opis, naglowki, akapity i punkty list. */
function readmeUnits(path) {
  const lines = readFileSync(path, 'utf8').split('\n')
  let i = 0
  while (lines[i].trim()) i++
  while (!lines[i].trim()) i++
  const units = [['short', lines[i].trim()]]
  let section = null
  let buf = []
  const flush = () => { if (buf.length) units.push(['para', buf.join('\n')]); buf = [] }
  for (const raw of lines.slice(i + 1)) {
    const s = raw.trimEnd()
    const h2 = s.match(/^==\s*(.+?)\s*==$/)
    const h3 = s.match(/^=\s*(.+?)\s*=$/)
    if (h2) {
      flush()
      section = h2[1]
      if (!SKIP_SECTIONS.has(section)) units.push(['header', section])
    } else if (section === 'Upgrade Notice') {
      continue
    } else if (h3) {
      flush()
      units.push(['header', h3[1]])
    } else if (!s) {
      flush()
    } else {
      const li = s.match(/^(?:\d+\.|\*)\s+(.*)$/)
      if (li) { flush(); units.push(['li', li[1]]) } else buf.push(s)
    }
  }
  flush()
  return units
}

const esc = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')

/** Markdown readme -> HTML w postaci, w jakiej katalog zapisuje oryginaly. */
function render(text) {
  let out = ''
  let pos = 0
  for (const m of text.matchAll(/`([^`]+)`|\[([^\]]+)\]\(([^)]+)\)|\*\*([^*]+)\*\*/g)) {
    out += esc(text.slice(pos, m.index))
    if (m[1] !== undefined) out += `<code>${esc(m[1])}</code>`
    else if (m[2] !== undefined) out += `<a href="${esc(m[3]).replace(/"/g, '&quot;')}">${esc(m[2])}</a>`
    else out += `<strong>${esc(m[4])}</strong>`
    pos = m.index + m[0].length
  }
  return out + esc(text.slice(pos))
}

function readmePairs() {
  const en = readmeUnits(join(ROOT, 'readme.txt'))
  const pl = readmeUnits(join(ROOT, 'docs', 'readme-pl.txt'))
  const at = en.findIndex(([kind], k) => !pl[k] || kind !== pl[k][0])
  if (en.length !== pl.length || at >= 0) {
    const k = at >= 0 ? at : Math.min(en.length, pl.length)
    console.error(`readme.txt (${en.length} jednostek) i docs/readme-pl.txt (${pl.length}) maja rozna strukture; pierwsza roznica na jednostce ${k}:`)
    console.error('  EN: ' + (en[k] ? `[${en[k][0]}] ${en[k][1].slice(0, 100)}` : '(brak)'))
    console.error('  PL: ' + (pl[k] ? `[${pl[k][0]}] ${pl[k][1].slice(0, 100)}` : '(brak)'))
    console.error('Nic nie zapisano.')
    process.exit(1)
  }
  const pairs = new Map()
  en.forEach(([, text], k) => pairs.set(render(text), render(pl[k][1])))
  // Po parowaniu, bo w obu plikach te teksty stoja po angielsku i para nadpisalaby tlumaczenie.
  for (const [id, tr] of Object.entries(README_FIXED)) pairs.set(id, tr)
  return pairs
}

function uiPairs() {
  const pairs = new Map()
  for (const e of parsePo(readFileSync(join(ROOT, 'languages', 'cookiezen-pl_PL.po'), 'utf8'))) {
    if (e.msgid && e.msgstr) pairs.set(e.msgid, e.msgstr)
  }
  return (id) => pairs.get(id) || (isIntentionallySame(id) ? id : '')
}

async function download(project) {
  const res = await fetch(`${BASE}/${project}/pl/default/export-translations/?format=po`, { headers: { 'Cache-Control': 'no-cache' } })
  if (!res.ok) throw new Error(`${project}: HTTP ${res.status}`)
  const text = await res.text()
  // Strona logowania albo blad posrednika tez potrafi przyjsc z kodem 200.
  if (!text.includes('Project-Id-Version') || !parsePo(text).some((e) => e.msgid)) throw new Error(`${project}: odpowiedz nie jest plikiem .po z oryginalami`)
  return text
}

const readme = readmePairs()
const ui = uiPairs()
const projects = [['stable', ui, false], ['dev', ui, false], ['stable-readme', (id) => readme.get(id), true], ['dev-readme', (id) => readme.get(id), true]]

// Najpierw wszystkie eksporty: przy bledzie sieci katalog wyjsciowy nie dostaje polowy swiezych plikow.
let exports
try {
  exports = await Promise.all(projects.map(([project]) => download(project)))
} catch (err) {
  console.error(`${err.message}. Nic nie zapisano.`)
  process.exit(1)
}

mkdirSync(OUT, { recursive: true })
projects.forEach(([project, translate, isReadme], k) => {
  const { text, missing, same, used } = fill(exports[k], translate)
  const file = join(OUT, `cookiezen-${project}-pl.po`)
  writeFileSync(file, text)
  const unused = isReadme ? [...readme.keys()].filter((id) => !used.has(id) && !(id in README_FIXED) && !isIntentionallySame(id)) : []
  const problems = missing.length + same.length + unused.length
  console.log(`${project}: ${problems ? 'do sprawdzenia' : 'komplet'} -> ${file}`)
  for (const id of missing) console.log('  BRAK TLUMACZENIA: ' + id.slice(0, 110))
  for (const id of same) console.log('  TLUMACZENIE = ORYGINAL: ' + id.slice(0, 110))
  for (const id of unused) console.log('  TEKST Z REPO BEZ ORYGINALU W EKSPORCIE: ' + id.slice(0, 110))
  if (problems) process.exitCode = 1
})
