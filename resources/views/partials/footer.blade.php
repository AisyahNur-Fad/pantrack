<footer class="footer flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500 py-4 px-6 sm:px-8 border-t border-slate-200/80 bg-white">
    <div class="flex items-center gap-2">
        <img src="{{ asset('storage/images/logo.png') }}" alt="Logo PANTRACK" class="w-5 h-5 object-contain">
        <span>&copy; {{ now()->year }} <strong class="font-semibold text-slate-700">{{ config('pantrack.perusahaan') }}</strong>. Hak cipta dilindungi.</span>
    </div>
    <span class="text-slate-400">PANTRACK — {{ config('pantrack.nama_lengkap') }}</span>
</footer>
