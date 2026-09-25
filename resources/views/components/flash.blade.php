@if (session('success'))
    <div class="rounded-3xl border border-emerald-500/20 bg-emerald-500/10 px-5 py-3 text-sm font-medium text-emerald-600 dark:text-emerald-400">
        {{ session('success') }}
    </div>
@endif
@if (session('error'))
    <div class="rounded-3xl border border-rose-500/20 bg-rose-500/10 px-5 py-3 text-sm font-medium text-rose-600 dark:text-rose-400">
        {{ session('error') }}
    </div>
@endif
@if ($errors->any())
    <div class="rounded-3xl border border-rose-500/20 bg-rose-500/10 px-5 py-3 text-sm font-medium text-rose-600 dark:text-rose-400">
        {{ $errors->first() }}
    </div>
@endif
