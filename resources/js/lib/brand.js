// Warna banner per brand (sama dengan desain katalog). Brand lain dapat warna dari palet berdasarkan nama.
const KNOWN = [
    [/netflix/i, '#7f0f13', '#ffffff'],
    [/spotify/i, '#0e5c2f', '#1ed760'],
    [/chatgpt|openai/i, '#0d7a61', '#ffffff'],
    [/youtube/i, '#ffffff', '#ff0000'],
    [/canva/i, 'linear-gradient(135deg, #7d2ae8, #00c4cc)', '#ffffff'],
    [/microsoft|office|365/i, '#ffffff', '#d83b01'],
    [/capcut/i, '#111111', '#ffffff'],
    [/google/i, '#ffffff', '#4285f4'],
    [/disney/i, '#0b1a5c', '#ffffff'],
    [/vidio/i, '#d6001c', '#ffffff'],
]

const PALETTE = [
    ['#1f2937', '#fbbf24'],
    ['#0f766e', '#ffffff'],
    ['#4338ca', '#ffffff'],
    ['#be123c', '#ffffff'],
    ['#f5ede3', '#b8860b'],
]

export function brandColors(nama) {
    const known = KNOWN.find(([re]) => re.test(nama))
    if (known) return { bg: known[1], fg: known[2] }
    const hash = [...nama].reduce((h, c) => (h * 31 + c.charCodeAt(0)) >>> 0, 7)
    const [bg, fg] = PALETTE[hash % PALETTE.length]
    return { bg, fg }
}
