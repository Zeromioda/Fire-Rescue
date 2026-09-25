<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-2xl font-semibold tracking-tight text-foreground">
                    {{ __('Firefighter Personnel Roster') }}
                </h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    BFAD Station 178 · Admin command only
                </p>
            </div>
        </div>
    </x-slot>

    @php($inputClass = 'w-full rounded-3xl border border-border bg-card/95 px-5 py-3 text-sm text-foreground shadow-2xl backdrop-blur-xl transition placeholder:text-muted-foreground/70 focus:border-primary focus:outline-none focus:ring-2 focus:ring-ring/30')

    <!-- Main Page Container with Alpine State -->
    <div class="py-8 relative" x-data="{ editModalOpen: false, currentPersonnel: {} }">

        <!-- Edit Personnel Modal -->
        <div x-show="editModalOpen"
             class="fixed inset-0 z-[999] flex items-center justify-center overflow-y-auto bg-background/70 p-4 backdrop-blur-sm"
             style="display: none;">

            <div class="relative z-10 my-auto max-h-[90vh] w-full max-w-md space-y-4 overflow-y-auto rounded-3xl border border-border bg-card/95 p-6 shadow-2xl backdrop-blur-xl" @click.away="editModalOpen = false">
                <div class="flex items-center justify-between border-b border-border pb-4">
                    <h3 class="text-lg font-semibold tracking-tight text-foreground">Edit Personnel Details</h3>
                    <button type="button" @click="editModalOpen = false" class="px-2 text-xl text-muted-foreground transition hover:text-foreground" aria-label="Close">&times;</button>
                </div>

                <form :action="currentPersonnel.update_url" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground">Full Name</label>
                        <input type="text" name="name" x-model="currentPersonnel.name" required class="{{ $inputClass }}">
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground">Badge / Serial No.</label>
                        <input type="text" name="badge_number" x-model="currentPersonnel.badge_number" required class="{{ $inputClass }}">
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground">Official Email</label>
                        <input type="email" name="email" x-model="currentPersonnel.email" required class="{{ $inputClass }}">
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground">System Role</label>
                        <select name="role" x-model="currentPersonnel.role" required class="{{ $inputClass }}">
                            <option value="Firefighter">Firefighter</option>
                            <option value="Admin">Admin</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground">New Password <span class="font-normal text-muted-foreground">(leave blank to keep current)</span></label>
                        <input type="password" name="password" class="{{ $inputClass }}">
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground">Confirm New Password</label>
                        <input type="password" name="password_confirmation" class="{{ $inputClass }}">
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-border pt-4 sm:flex-row sm:justify-end">
                        <button type="button" @click="editModalOpen = false" class="inline-flex items-center justify-center gap-2 rounded-3xl border border-border bg-card/95 px-6 py-3 text-sm font-semibold text-foreground shadow-2xl backdrop-blur-xl transition hover:bg-card-alt">Cancel</button>
                        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-3xl border border-primary/20 bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground shadow-2xl backdrop-blur-xl transition hover:bg-primary/90">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="rounded-3xl border border-border bg-card/95 p-5 text-sm font-medium text-emerald-600 shadow-2xl backdrop-blur-xl dark:text-emerald-400">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="space-y-1 rounded-3xl border border-border bg-card/95 p-5 text-sm text-destructive shadow-2xl backdrop-blur-xl">
                    <p class="font-semibold">Please fix the following errors:</p>
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Form: Register New Personnel -->
            <details class="group rounded-3xl border border-border bg-card/95 p-6 shadow-2xl backdrop-blur-xl" open>
                <summary class="flex cursor-pointer flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                    <span class="text-lg font-semibold tracking-tight text-foreground">Register New Firefighter / Personnel</span>
                    <span class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Admin privileged form</span>
                </summary>

                <form action="{{ route('admin.firefighters.store') }}" method="POST" class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
                    @csrf

                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground">Full Name</label>
                        <input type="text" name="name" value="{{ old('name') }}" required placeholder="John Doe" class="{{ $inputClass }}">
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground">Badge / Serial No.</label>
                        <input type="text" name="badge_number" value="{{ old('badge_number') }}" required placeholder="BFAD-178-088" class="{{ $inputClass }}">
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground">Official Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" required placeholder="j.doe@bfad178.gov.ph" class="{{ $inputClass }}">
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground">Role</label>
                        <select name="role" required class="{{ $inputClass }}">
                            <option value="Firefighter">Firefighter</option>
                            <option value="Admin">Admin</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground">Default Password</label>
                        <input type="password" name="password" required class="{{ $inputClass }}">
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-foreground">Confirm Password</label>
                        <input type="password" name="password_confirmation" required class="{{ $inputClass }}">
                    </div>

                    <div class="flex justify-end sm:col-span-2 md:col-span-3">
                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-3xl border border-primary/20 bg-primary px-6 py-3 text-sm font-semibold text-primary-foreground shadow-2xl backdrop-blur-xl transition hover:bg-primary/90 sm:w-auto">
                            Register Personnel
                        </button>
                    </div>
                </form>
            </details>

            <!-- Personnel Roster Table -->
            <div class="space-y-4 rounded-3xl border border-border bg-card/95 p-6 shadow-2xl backdrop-blur-xl">
                <h3 class="text-lg font-semibold tracking-tight text-foreground">Active Station Personnel Roster</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-border">
                            <tr class="text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                <th class="whitespace-nowrap px-4 py-3 font-medium">Badge No</th>
                                <th class="whitespace-nowrap px-4 py-3 font-medium">Name</th>
                                <th class="whitespace-nowrap px-4 py-3 font-medium">Email</th>
                                <th class="whitespace-nowrap px-4 py-3 font-medium">Role</th>
                                <th class="whitespace-nowrap px-4 py-3 font-medium">Registered On</th>
                                <th class="whitespace-nowrap px-4 py-3 text-right font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @forelse($firefighters as $personnel)
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm font-semibold tabular-nums text-primary">{{ $personnel->badge_number ?? $personnel->badge ?? $personnel->badge_id ?? $personnel->serial_number ?? 'N/A' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-foreground">{{ $personnel->name }}</td>
                                    <td class="px-4 py-3 text-sm text-muted-foreground">{{ $personnel->email }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="whitespace-nowrap rounded-full px-3 py-1 text-xs font-medium {{ $personnel->hasRole('Admin') ? 'bg-rose-500/10 text-rose-600 dark:text-rose-400' : 'bg-sky-500/10 text-sky-600 dark:text-sky-400' }}">
                                            {{ ucfirst($personnel->getRoleNames()->first() ?? 'Firefighter') }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm tabular-nums text-muted-foreground">{{ $personnel->created_at->format('Y-m-d') }}</td>
                                    <td class="px-4 py-3 text-right text-sm">
                                        <button type="button"
                                                @click="editModalOpen = true; currentPersonnel = {
                                                    id: '{{ $personnel->id }}',
                                                    name: '{{ addslashes($personnel->name) }}',
                                                    badge_number: '{{ $personnel->badge_number ?? $personnel->badge ?? $personnel->badge_id ?? $personnel->serial_number ?? '' }}',
                                                    email: '{{ $personnel->email }}',
                                                    role: '{{ $personnel->getRoleNames()->first() ?? 'Firefighter' }}',
                                                    update_url: '{{ route('admin.firefighters.update', $personnel->id) }}'
                                                }"
                                                class="inline-flex items-center justify-center gap-2 rounded-3xl border border-border bg-card/95 px-4 py-2 text-xs font-semibold text-foreground shadow-2xl backdrop-blur-xl transition hover:bg-card-alt">
                                            Edit
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-sm text-muted-foreground">No personnel found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>
</x-app-layout>
