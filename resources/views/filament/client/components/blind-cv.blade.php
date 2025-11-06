<div class="space-y-6">
    <!-- Header -->
    <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-blue-900">Blind CV</h2>
                <p class="text-blue-700 text-sm">Data pribadi kandidat telah disembunyikan untuk menjaga kerahasiaan</p>
            </div>
            <div class="text-right">
                <p class="text-lg font-bold text-blue-900">{{ $blindCV['unique_id'] ?? 'TALENT-ID' }}</p>
                <p class="text-sm text-blue-700">Talent ID</p>
            </div>
        </div>
    </div>

    <!-- Experience -->
    <div class="border border-gray-200 rounded-lg">
        <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
            <h3 class="font-semibold text-gray-900">📊 Ringkasan Pengalaman</h3>
        </div>
        <div class="p-4">
            <p class="text-gray-700 whitespace-pre-line">{{ $blindCV['experience'] ?? 'Tidak ada informasi pengalaman' }}</p>
        </div>
    </div>

    <!-- Skills -->
    <div class="border border-gray-200 rounded-lg">
        <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
            <h3 class="font-semibold text-gray-900">🛠️ Keahlian Teknis</h3>
        </div>
        <div class="p-4">
            <p class="text-gray-700 whitespace-pre-line">{{ $blindCV['skills'] ?? 'Tidak ada informasi keahlian' }}</p>
        </div>
    </div>

    <!-- Education -->
    <div class="border border-gray-200 rounded-lg">
        <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
            <h3 class="font-semibold text-gray-900">🎓 Latar Belakang Pendidikan</h3>
        </div>
        <div class="p-4">
            <p class="text-gray-700 whitespace-pre-line">{{ $blindCV['education'] ?? 'Tidak ada informasi pendidikan' }}</p>
        </div>
    </div>

    <!-- Note -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800">Informasi Penting</h3>
                <div class="mt-2 text-sm text-yellow-700">
                    <p>• Data pribadi seperti nama, email, telepon, dan media sosial telah disembunyikan</p>
                    <p>• Untuk informasi lebih lanjut, hubungi tim TalentGO</p>
                </div>
            </div>
        </div>
    </div>
</div>