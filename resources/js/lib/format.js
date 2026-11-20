export const rupiah = (n) => 'Rp ' + Number(n ?? 0).toLocaleString('id-ID')

export const rupiahRingkas = (n) => {
    const v = Number(n ?? 0)
    if (v >= 1_000_000_000) return `${(v / 1_000_000_000).toLocaleString('id-ID', { maximumFractionDigits: 1 })} M`
    if (v >= 1_000_000) return `${(v / 1_000_000).toLocaleString('id-ID', { maximumFractionDigits: 1 })} jt`
    if (v >= 1_000) return `${(v / 1_000).toLocaleString('id-ID', { maximumFractionDigits: 0 })} rb`
    return String(v)
}

export const angka = (n) => Number(n ?? 0).toLocaleString('id-ID')
