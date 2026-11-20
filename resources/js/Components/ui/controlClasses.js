// Kelas bersama semua kontrol form agar tinggi, radius, border, dan fokus seragam.
export const controlSize = {
    sm: 'h-9 text-sm',
    md: 'h-11 text-sm',
}

export const controlBase =
    'w-full rounded-xl border-slate-300 bg-white text-ink placeholder:text-slate-400 shadow-none transition ' +
    'focus:border-primary focus:ring-2 focus:ring-primary-ring/50 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-400'

export const controlError = 'border-rose-400 focus:border-rose-500 focus:ring-rose-200'

let uid = 0
export const nextId = (prefix = 'ui') => `${prefix}-${++uid}`
