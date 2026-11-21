/**
 * Mengubah teks sederhana menjadi blok aman (tanpa v-html):
 * paragraf dipisah baris kosong, baris "- " menjadi poin, **teks** menjadi tebal.
 */
const inline = (text) =>
    text.split(/(\*\*[^*]+\*\*)/g).filter(Boolean).map((part) =>
        part.startsWith('**') && part.endsWith('**') && part.length > 4 ? { text: part.slice(2, -2), bold: true } : { text: part, bold: false },
    )

export const parseRichText = (source) => {
    const blocks = []
    let paragraph = []
    let list = null
    const flushParagraph = () => {
        if (paragraph.length) blocks.push({ type: 'p', parts: inline(paragraph.join(' ')) })
        paragraph = []
    }
    const flushList = () => {
        if (list) blocks.push({ type: 'ul', items: list.map(inline) })
        list = null
    }

    for (const raw of String(source ?? '').split(/\r?\n/)) {
        const line = raw.trim()
        if (/^[-*•]\s+/.test(line)) {
            flushParagraph()
            ;(list ??= []).push(line.replace(/^[-*•]\s+/, ''))
        } else if (line === '') {
            flushParagraph()
            flushList()
        } else {
            flushList()
            paragraph.push(line)
        }
    }
    flushParagraph()
    flushList()

    return blocks
}

export const fillPlaceholders = (text, values) => String(text ?? '').replace(/\{(\w+)\}/g, (match, key) => values[key] ?? match)
