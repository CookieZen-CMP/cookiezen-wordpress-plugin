/**
 * Generator assetow WordPress.org (icon + banner) dla wtyczki CookieZen.
 *
 * Rasteryzuje logo marki (public/SygnetAlter.svg + public/CookieZenBlack.svg)
 * do plikow PNG wymaganych przez katalog WP.org (SVN /assets/):
 *   - icon-256x256.png, icon-128x128.png
 *   - banner-772x250.png, banner-1544x500.png (retina 2x), po jednej parze
 *     na kazdy wpis w BANNER_LOCALES
 *
 * Wymaga pakietu `sharp` (rasteryzacja SVG). Uruchamiac z srodowiska Node,
 * ktore ma zainstalowany sharp, np.:
 *   node scripts/generate-wporg-assets.mjs ./.wporg-assets
 *
 * Assety NIE wchodza do ZIP-a wtyczki — trafiaja wylacznie do SVN /assets/.
 */
import sharp from 'sharp'
import { mkdirSync } from 'node:fs'

const OUT = process.argv[2]
if (!OUT) { console.error('Usage: node gen-wporg-assets.mjs <out-dir>'); process.exit(1) }
mkdirSync(OUT, { recursive: true })

// --- Brand palette (Emerald Sea) ---
const NAVY = '#141D32'
const BLUE = '#1A3A66'
const GREEN = '#72BE8C'

// Sygnet (mark) inner content from public/SygnetAlter.svg, viewBox 0 0 41.55 44.08
const sygnet = (gradId) => `
  <defs>
    <linearGradient id="${gradId}" x1="15.59" y1="28.95" x2="36.36" y2="15.51" gradientUnits="userSpaceOnUse">
      <stop offset="0" stop-color="${GREEN}"/>
      <stop offset="1" stop-color="${BLUE}"/>
    </linearGradient>
  </defs>
  <g>
    <path fill="url(#${gradId})" d="M38.94,7.83l-9.91-3.78.32,5v5.69l-1.01,2.66-2.36,2.3-.68.81-3.26.7-4.34.83-3.14.77-2.27,4.03s-.16,6.17,0,6.86c.16.69,3.41,4.68,3.92,4.84.51.16,4.56,2.6,4.56,2.6l4.53-2.27,7.62-5.94,4.5-7.38,2.33-11.73-.81-5.98ZM20.78,33.7c-1.91,0-3.07-1.24-3.07-2.84s1.15-2.82,3.07-2.82,3.07,1.21,3.07,2.82-1.15,2.84-3.07,2.84Z"/>
    <path fill="${NAVY}" d="M21.24,44c-.31.11-.65.1-.93,0-.86-.3-1.64-.63-2.46-1.02-5.18-2.5-9.8-6.05-13-10.9-1.95-2.97-3.26-6.29-3.88-9.8C.35,18.71-.22,13.11.09,9.55c.22-2.5,1.88-4.48,4.27-5.2L17.08.54c2.46-.74,4.97-.72,7.42.01l9.5,2.85,3.23.96c1.03.31,1.93.88,2.66,1.66.89.95,1.41,2.12,1.54,3.42.12,1.21.15,2.35.09,3.58-.16,2.94-.43,5.82-.9,8.73-.45,2.81-1.3,5.5-2.6,8.02-3,5.85-8.15,10.17-13.98,13.04-.93.46-1.8.84-2.79,1.19M10.73,32.92c-1.28-5.37,1.52-10.7,6.6-12.63,1.18-.45,2.43-.64,3.73-.65,4.21-.2,7.5-3.91,7.27-8.13-.17-3.06-2.07-5.78-4.92-6.93-1.47-.59-3.14-.72-4.69-.26l-13.26,3.97c-.64.19-1.16.71-1.26,1.35-.11.78-.13,1.51-.12,2.3.06,2.92.31,5.79.76,8.68.73,4.64,2.6,8.82,5.87,12.3M20.78,39.76c2.71-1.17,5.17-2.62,7.43-4.43,4.55-3.63,7.39-8.45,8.36-14.2.54-3.21,1.01-7.94.79-11.16-.05-.77-.51-1.42-1.28-1.65l-6.47-1.93c2.48,4.23,1.88,9.46-1.5,12.9-1.94,2.04-4.6,3.14-7.41,3.18-2.99.04-5.63,1.86-6.82,4.56-.94,2.13-.91,4.47,0,6.62.91,2.05,2.44,3.76,4.38,4.9.81.48,1.63.83,2.51,1.21"/>
    <path fill="${NAVY}" d="M20.78,9.05c-1.91,0-3.07,1.21-3.07,2.82s1.15,2.84,3.07,2.84,3.07-1.24,3.07-2.84-1.15-2.82-3.07-2.82"/>
  </g>`

// Wordmark from public/CookieZenBlack.svg, viewBox 0 0 193.96 53.09 (drawn in NAVY)
const wordmark = () => `
  <g fill="${NAVY}">
    <path d="M57.84,27.47c0-5.23,4.02-8.98,9.42-8.98,3,0,5.5,1.09,7.14,3.08l-2.58,2.38c-1.16-1.34-2.63-2.03-4.36-2.03-3.25,0-5.55,2.28-5.55,5.55s2.31,5.55,5.55,5.55c1.74,0,3.2-.69,4.36-2.06l2.58,2.38c-1.64,2.01-4.14,3.1-7.16,3.1-5.38,0-9.4-3.74-9.4-8.98"/>
    <path d="M75.37,29.48c0-4.02,3.1-6.87,7.34-6.87s7.32,2.85,7.32,6.87-3.08,6.87-7.32,6.87-7.34-2.85-7.34-6.87M86.11,29.48c0-2.31-1.46-3.69-3.4-3.69s-3.42,1.39-3.42,3.69,1.49,3.69,3.42,3.69,3.4-1.39,3.4-3.69"/>
    <path d="M91.61,29.48c0-4.02,3.1-6.87,7.34-6.87s7.32,2.85,7.32,6.87-3.08,6.87-7.32,6.87-7.34-2.85-7.34-6.87M102.35,29.48c0-2.31-1.46-3.69-3.4-3.69s-3.42,1.39-3.42,3.69,1.49,3.69,3.42,3.69,3.4-1.39,3.4-3.69"/>
    <polygon points="114.57 30.92 112.71 32.75 112.71 36.15 108.84 36.15 108.84 17.75 112.71 17.75 112.71 28.17 118.36 22.81 122.98 22.81 117.42 28.46 123.47 36.15 118.79 36.15 114.57 30.92"/>
    <path d="M124.64,18.79c0-1.22.97-2.16,2.41-2.16s2.4.89,2.4,2.08c0,1.29-.97,2.23-2.4,2.23s-2.41-.94-2.41-2.16M125.11,22.81h3.87v13.34h-3.87v-13.34Z"/>
    <path d="M145.56,30.57h-10.09c.37,1.66,1.79,2.68,3.82,2.68,1.41,0,2.43-.42,3.35-1.29l2.06,2.23c-1.24,1.41-3.1,2.16-5.5,2.16-4.61,0-7.61-2.9-7.61-6.87s3.05-6.87,7.12-6.87,6.94,2.63,6.94,6.92c0,.3-.05.72-.07,1.04M135.42,28.32h6.57c-.27-1.69-1.54-2.78-3.27-2.78s-3.03,1.07-3.3,2.78"/>
    <polygon points="162.55 32.88 162.55 36.15 147.37 36.15 147.37 33.55 156.89 22.07 147.57 22.07 147.57 18.79 162.17 18.79 162.17 21.4 152.68 32.88 162.55 32.88"/>
    <path d="M177.67,30.57h-10.09c.37,1.66,1.79,2.68,3.82,2.68,1.41,0,2.43-.42,3.35-1.29l2.06,2.23c-1.24,1.41-3.1,2.16-5.5,2.16-4.61,0-7.61-2.9-7.61-6.87s3.05-6.87,7.12-6.87,6.94,2.63,6.94,6.92c0,.3-.05.72-.07,1.04M167.53,28.32h6.57c-.27-1.69-1.54-2.78-3.27-2.78s-3.03,1.07-3.3,2.78"/>
    <path d="M193.96,28.51v7.64h-3.87v-7.04c0-2.16-.99-3.15-2.7-3.15-1.86,0-3.2,1.14-3.2,3.6v6.6h-3.87v-13.34h3.69v1.56c1.04-1.14,2.6-1.76,4.41-1.76,3.15,0,5.53,1.83,5.53,5.9"/>
    <path d="M25.58,52.99c-.38.13-.78.12-1.12,0-1.03-.36-1.97-.75-2.96-1.23-6.24-3.01-11.81-7.29-15.65-13.13-2.35-3.58-3.92-7.58-4.67-11.8C.42,22.53-.27,15.79.11,11.51c.26-3.01,2.26-5.4,5.14-6.26L20.57.66c2.96-.89,5.98-.87,8.93.02l11.44,3.43,3.89,1.16c1.24.37,2.32,1.05,3.21,2,1.07,1.14,1.7,2.55,1.85,4.11.15,1.45.18,2.84.1,4.31-.19,3.54-.52,7.01-1.08,10.51-.55,3.38-1.57,6.62-3.13,9.66-3.61,7.05-9.82,12.24-16.84,15.7-1.12.55-2.17,1.01-3.37,1.43M12.93,39.65c-1.54-6.47,1.83-12.88,7.95-15.21,1.42-.54,2.92-.77,4.49-.78,5.07-.24,9.03-4.71,8.75-9.79-.2-3.68-2.49-6.96-5.92-8.35-1.77-.72-3.78-.87-5.64-.31l-15.97,4.79c-.77.23-1.4.86-1.51,1.62-.14.94-.16,1.81-.14,2.77.07,3.52.37,6.97.92,10.45.88,5.59,3.13,10.63,7.07,14.81M25.03,47.89c3.26-1.4,6.22-3.16,8.95-5.33,5.47-4.37,8.9-10.17,10.07-17.1.65-3.87,1.22-9.57.96-13.44-.06-.92-.61-1.72-1.54-1.99l-7.8-2.33c2.99,5.1,2.27,11.39-1.81,15.54-2.34,2.46-5.54,3.78-8.92,3.83-3.6.05-6.78,2.24-8.22,5.49-1.14,2.57-1.1,5.39,0,7.98,1.09,2.46,2.94,4.53,5.28,5.9.98.57,1.96,1,3.02,1.46"/>
    <path d="M25.02,10.9c-2.31,0-3.7,1.46-3.7,3.4s1.39,3.42,3.7,3.42,3.69-1.49,3.69-3.42-1.39-3.4-3.69-3.4"/>
    <path d="M25.02,33.48c-2.31,0-3.7,1.46-3.7,3.4s1.39,3.42,3.7,3.42,3.69-1.49,3.69-3.42-1.39-3.4-3.69-3.4"/>
  </g>`

// --- ICON: rounded white card with centered sygnet ---
function iconSvg(size) {
  const r = Math.round(size * 0.20)
  const inner = Math.round(size * 0.56)         // sygnet target height
  const w = inner * (41.55 / 44.08)             // keep aspect ratio
  const x = (size - w) / 2
  const y = (size - inner) / 2
  return `<svg xmlns="http://www.w3.org/2000/svg" width="${size}" height="${size}" viewBox="0 0 ${size} ${size}">
    <rect width="${size}" height="${size}" rx="${r}" ry="${r}" fill="#FFFFFF"/>
    <svg x="${x}" y="${y}" width="${w}" height="${inner}" viewBox="0 0 41.55 44.08">${sygnet('ig' + size)}</svg>
  </svg>`
}

/*
 * Warianty jezykowe banera. Katalog WP.org serwuje plik z sufiksem lokalizacji
 * odwiedzajacemu w tym jezyku, a plik bez sufiksu wszystkim pozostalym, wiec
 * domyslny wariant jest angielski, tak jak readme.txt. Sufiks '' MUSI zostac
 * pierwszy: to on daje plik bez sufiksu, ktory katalog traktuje jako domyslny.
 */
const BANNER_LOCALES = [
  {
    suffix: '',
    tagline: 'Cookie consent management without the stress',
    pills: ['Consent Mode v2', 'GDPR and ePrivacy', 'Cookie scanner']
  },
  {
    suffix: '-pl_PL',
    tagline: 'Zarządzanie zgodami cookies bez stresu',
    pills: ['Consent Mode v2', 'RODO i ePrivacy', 'Skaner cookies']
  }
]

const FONT = 'Helvetica, Arial, sans-serif'

/*
 * Litera C wordmarku zaczyna sie na x=57.84, a nie na krawedzi viewBoxa. Kadr
 * musi zaczac sie dokladnie tam, inaczej nazwa jest wcieta wobec tagline'u
 * i plakietek, ktore startuja rowno od `textX`.
 */
const LOGO_CROP_X = 57.84
const LOGO_CROP_W = 193.96 - LOGO_CROP_X

/* Margines plytki pod sygnetem jako ulamek jego wymiaru, jak w karcie OG. */
const PLATE_PADDING = 0.07

/*
 * BANNER: gradient, plytka pod sygnetem i stopka sa przeniesione z generatora
 * karty OG (cmp-app, scripts/generate-og-image.mjs), zeby obie powierzchnie
 * marki mowily jednym jezykiem. Zmiana kolorow tla albo plytki powinna isc
 * rownolegle w obu plikach.
 */
function bannerSvg(W, H, locale) {
  const s = W / 772                              // scale factor from base design

  const sygH = 160 * s
  const sygW = sygH * (41.55 / 44.08)
  const plateW = sygW * (1 + PLATE_PADDING * 2)
  const plateH = sygH * (1 + PLATE_PADDING * 2)
  const plateX = 32 * s
  const plateY = (H - plateH) / 2

  const textX = plateX + plateW + 40 * s
  const logoW = 212 * s
  const logoH = logoW * (53.09 / LOGO_CROP_W)
  const taglineSize = 17 * s
  const pillH = 30 * s
  const blockH = logoH + 16 * s + taglineSize + 26 * s + pillH
  const logoY = (H - blockH) / 2

  /*
   * Kadr wordmarku niesie pusty pas pod literami, bo glify koncza sie mniej
   * wiecej na 68 procentach wysokosci viewBoxa. Odstep optyczny jest wiec
   * wiekszy niz liczbowy, wiec odjecie `18 * s` w linii nizej podnosi dwie
   * dolne linie o te roznice, nie ruszajac logo ani osi calego bloku.
   */
  const taglineBaseline = logoY + logoH + 16 * s + taglineSize - 18 * s
  const pillY = taglineBaseline + 26 * s

  let cx = textX
  const pills = locale.pills
    .map((label) => {
      const w = (label.length * 7.3 + 30) * s
      const el = `<rect x="${cx}" y="${pillY}" width="${w}" height="${pillH}" rx="${pillH / 2}" fill="#FFFFFF"/>
    <text x="${cx + w / 2}" y="${pillY + pillH * 0.66}" font-family="${FONT}" font-size="${14 * s}" font-weight="600" fill="${BLUE}" text-anchor="middle">${label}</text>`
      cx += w + 10 * s
      return el
    })
    .join('\n    ')

  return `<svg xmlns="http://www.w3.org/2000/svg" width="${W}" height="${H}" viewBox="0 0 ${W} ${H}">
    <defs>
      <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
        <stop offset="0%" stop-color="${BLUE}"/>
        <stop offset="100%" stop-color="#0C1422"/>
      </linearGradient>
      <radialGradient id="glow" cx="85%" cy="100%" r="60%">
        <stop offset="0%" stop-color="${GREEN}" stop-opacity="0.25"/>
        <stop offset="100%" stop-color="${GREEN}" stop-opacity="0"/>
      </radialGradient>
    </defs>
    <rect width="${W}" height="${H}" fill="url(#bg)"/>
    <rect width="${W}" height="${H}" fill="url(#glow)"/>
    <rect x="${plateX}" y="${plateY}" width="${plateW}" height="${plateH}" rx="${plateW * 0.13}" fill="#F8FAFC"/>
    <svg x="${plateX + (plateW - sygW) / 2}" y="${plateY + (plateH - sygH) / 2}" width="${sygW}" height="${sygH}" viewBox="0 0 41.55 44.08">${sygnet('bs' + W)}</svg>
    <svg x="${textX}" y="${logoY}" width="${logoW}" height="${logoH}" viewBox="${LOGO_CROP_X} 0 ${LOGO_CROP_W} 53.09">${wordmark().replace(`fill="${NAVY}"`, 'fill="#FFFFFF"')}</svg>
    <text x="${textX}" y="${taglineBaseline}" font-family="${FONT}" font-size="${taglineSize}" fill="#CBD5E1">${locale.tagline}</text>
    ${pills}
    <text x="${W - 26 * s}" y="${H - 18 * s}" font-family="${FONT}" font-size="${14 * s}" font-weight="500" fill="${GREEN}" text-anchor="end">cookiezen.pl</text>
  </svg>`
}

async function render(svg, file) {
  await sharp(Buffer.from(svg)).png().toFile(`${OUT}/${file}`)
  console.log('wrote', file)
}

await render(iconSvg(256), 'icon-256x256.png')
await render(iconSvg(128), 'icon-128x128.png')

for (const locale of BANNER_LOCALES) {
  await render(bannerSvg(772, 250, locale), `banner-772x250${locale.suffix}.png`)
  await render(bannerSvg(1544, 500, locale), `banner-1544x500${locale.suffix}.png`)
}
console.log('done')
