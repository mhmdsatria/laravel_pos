<td class="px-lg py-4 text-center">
    <div class="flex items-center justify-center gap-2">
        <a
            href="{{ route('sales.show', $history->id) }}"
            class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-on-surface-variant hover:bg-primary/10 hover:text-primary transition-colors"
            title="Lihat detail transaksi"
        >
            <span class="material-symbols-outlined text-[20px]">visibility</span>
        </a>

        <a
            href="{{ route('sales.print', $history->id) }}"
            target="_blank"
            rel="noopener"
            class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-on-surface-variant hover:bg-primary/10 hover:text-primary transition-colors"
            title="Cetak nota"
        >
            <span class="material-symbols-outlined text-[20px]">receipt</span>
        </a>
    </div>
</td>
