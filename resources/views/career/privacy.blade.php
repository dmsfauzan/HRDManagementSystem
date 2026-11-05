<x-public-layout :title="'Kebijakan Privasi'">
    <div class="max-w-3xl mx-auto bg-white rounded-xl border border-slate-200 p-6 sm:p-8">
        <h1 class="text-xl font-bold text-slate-900">Kebijakan Privasi Pelamar</h1>
        <p class="mt-2 text-sm text-slate-500">Terakhir diperbarui: {{ now()->translatedFormat('d F Y') }}</p>

        <div class="mt-6 space-y-4 text-sm text-slate-700 leading-relaxed">
            <p>Kami menghormati privasi Anda. Kebijakan ini menjelaskan bagaimana data pelamar diproses sesuai prinsip pelindungan data pribadi.</p>

            <h2 class="text-sm font-semibold text-slate-900">1. Data yang dikumpulkan</h2>
            <p>Nama, email, nomor telepon, domisili, tanggal lahir, pendidikan, pengalaman, tautan (LinkedIn/portofolio), ekspektasi gaji, surat lamaran, CV, dan foto (opsional).</p>

            <h2 class="text-sm font-semibold text-slate-900">2. Tujuan pemrosesan</h2>
            <p>Data digunakan semata-mata untuk menilai lamaran, menghubungi Anda terkait proses rekrutmen, dan memenuhi kewajiban hukum yang berlaku.</p>

            <h2 class="text-sm font-semibold text-slate-900">3. Penyimpanan & keamanan</h2>
            <p>Berkas CV dan foto disimpan pada penyimpanan privat dengan akses terbatas, tidak dapat diakses publik. Data hanya dapat diakses oleh tim rekrutmen yang berwenang.</p>

            <h2 class="text-sm font-semibold text-slate-900">4. Retensi</h2>
            <p>Data pelamar disimpan selama diperlukan untuk proses rekrutmen dan dapat dihapus setelah periode retensi yang berlaku. Anda dapat meminta penghapusan data dengan menghubungi kami.</p>

            <h2 class="text-sm font-semibold text-slate-900">5. Hak Anda</h2>
            <p>Anda berhak mengakses, memperbaiki, atau meminta penghapusan data pribadi Anda. Hubungi tim HR melalui kanal resmi perusahaan.</p>

            <h2 class="text-sm font-semibold text-slate-900">6. Persetujuan</h2>
            <p>Dengan mengirimkan lamaran, Anda menyatakan telah membaca dan menyetujui pemrosesan data pribadi sesuai kebijakan ini.</p>
        </div>

        <a href="{{ route('career.index') }}" class="mt-8 inline-block text-sm text-emerald-700 hover:underline">&larr; Kembali ke lowongan</a>
    </div>
</x-public-layout>
