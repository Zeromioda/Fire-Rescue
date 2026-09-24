<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-display font-black text-2xl text-foreground tracking-tight flex items-center gap-2">
                    👨‍🚒 {{ __('Firefighter Personnel Roster') }}
                </h2>
                <p class="text-xs text-muted font-mono uppercase tracking-wider mt-0.5">
                    BFAD Station 178 • Admin Command Only
                </p>
            </div>
        </div>
    </x-slot>

    <!-- Main Page Container with Alpine State -->
    <div class="py-8 bg-background min-h-screen text-foreground relative" x-data="{ editModalOpen: false, currentPersonnel: {} }">
        
        <!-- ABSOLUTE FULLSCREEN OVERLAY MODAL -->
        <div x-show="editModalOpen" 
             class="fixed inset-0 z-[999] flex items-center justify-center bg-black/80 backdrop-blur-sm p-4 overflow-y-auto" 
             style="display: none;">
            
            <!-- Modal Card Box: Made completely solid with bg-zinc-900 to eliminate transparency -->
            <div class="bg-zinc-900 border border-zinc-700 rounded-2xl p-6 max-w-md w-full shadow-2xl space-y-4 my-auto max-h-[90vh] overflow-y-auto relative z-10" @click.away="editModalOpen = false">
                <div class="flex justify-between items-center border-b border-zinc-800 pb-3">
                    <h3 class="font-black text-base text-white">Edit Personnel Details</h3>
                    <button type="button" @click="editModalOpen = false" class="text-zinc-400 hover:text-white text-xl font-bold px-2">&times;</button>
                </div>

                <form :action="currentPersonnel.update_url" method="POST" class="space-y-3">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-[10px] font-bold uppercase text-zinc-400 mb-1">Full Name</label>
                        <input type="text" name="name" x-model="currentPersonnel.name" required class="w-full bg-zinc-950 border border-zinc-700 rounded-xl p-2.5 text-xs text-white focus:outline-none focus:border-rose-600">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase text-zinc-400 mb-1">Badge / Serial No.</label>
                        <input type="text" name="badge_number" x-model="currentPersonnel.badge_number" required class="w-full bg-zinc-950 border border-zinc-700 rounded-xl p-2.5 text-xs text-white font-mono focus:outline-none focus:border-rose-600">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase text-zinc-400 mb-1">Official Email</label>
                        <input type="email" name="email" x-model="currentPersonnel.email" required class="w-full bg-zinc-950 border border-zinc-700 rounded-xl p-2.5 text-xs text-white focus:outline-none focus:border-rose-600">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase text-zinc-400 mb-1">System Role</label>
                        <select name="role" x-model="currentPersonnel.role" required class="w-full bg-zinc-950 border border-zinc-700 rounded-xl p-2.5 text-xs text-white focus:outline-none focus:border-rose-600">
                            <option value="Firefighter">Firefighter</option>
                            <option value="Admin">Admin</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase text-zinc-400 mb-1">New Password <span class="text-zinc-500 font-normal">(Leave blank to keep current)</span></label>
                        <input type="password" name="password" class="w-full bg-zinc-950 border border-zinc-700 rounded-xl p-2.5 text-xs text-white focus:outline-none focus:border-rose-600">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase text-zinc-400 mb-1">Confirm New Password</label>
                        <input type="password" name="password_confirmation" class="w-full bg-zinc-950 border border-zinc-700 rounded-xl p-2.5 text-xs text-white focus:outline-none focus:border-rose-600">
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t border-zinc-800">
                        <button type="button" @click="editModalOpen = false" class="px-4 py-2 bg-zinc-800 text-zinc-300 text-xs font-bold rounded-xl hover:bg-zinc-700">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white text-xs font-black rounded-xl">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 font-bold text-xs rounded-xl">
                    ✓ {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="p-4 bg-rose-500/10 border border-rose-500/20 text-rose-400 font-bold text-xs rounded-xl space-y-1">
                    <p>⚠️ Please fix the following errors:</p>
                    @foreach ($errors->all() as $error)
                        <p>• {{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <!-- Form: Register New Personnel -->
            <details class="bg-card border border-border rounded-2xl p-6 shadow-sm group" open>
                <summary class="font-black text-base text-foreground cursor-pointer flex items-center justify-between">
                    <span>+ Register New Firefighter / Personnel</span>
                    <span class="text-xs text-rose-500 font-bold uppercase tracking-wider">Admin Privileged Form</span>
                </summary>

                <form action="{{ route('admin.firefighters.store') }}" method="POST" class="mt-4 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    @csrf

                    <div>
                        <label class="block text-[10px] font-bold uppercase text-muted mb-1">Full Name</label>
                        <input type="text" name="name" value="{{ old('name') }}" required placeholder="John Doe" class="w-full bg-background border border-border rounded-xl p-2.5 text-xs text-foreground">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase text-muted mb-1">Badge / Serial No.</label>
                        <input type="text" name="badge_number" value="{{ old('badge_number') }}" required placeholder="BFAD-178-088" class="w-full bg-background border border-border rounded-xl p-2.5 text-xs text-foreground font-mono">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase text-muted mb-1">Official Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" required placeholder="j.doe@bfad178.gov.ph" class="w-full bg-background border border-border rounded-xl p-2.5 text-xs text-foreground">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase text-muted mb-1">Role</label>
                        <select name="role" required class="w-full bg-background border border-border rounded-xl p-2.5 text-xs text-foreground">
                            <option value="Firefighter">Firefighter</option>
                            <option value="Admin">Admin</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase text-muted mb-1">Default Password</label>
                        <input type="password" name="password" required class="w-full bg-background border border-border rounded-xl p-2.5 text-xs text-foreground">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase text-muted mb-1">Confirm Password</label>
                        <input type="password" name="password_confirmation" required class="w-full bg-background border border-border rounded-xl p-2.5 text-xs text-foreground">
                    </div>

                    <div class="sm:col-span-2 md:col-span-3 flex justify-end">
                        <button type="submit" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-500 text-white font-black text-xs rounded-xl shadow-md uppercase tracking-wider">
                            Register Personnel
                        </button>
                    </div>
                </form>
            </details>

            <!-- Personnel Roster Table -->
            <div class="bg-card border border-border rounded-2xl p-6 shadow-sm space-y-4">
                <h3 class="font-black text-lg text-foreground">Active Station Personnel Roster</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-border uppercase text-[10px] font-mono text-muted">
                            <tr>
                                <th class="py-3 px-2">Badge No</th>
                                <th class="py-3 px-2">Name</th>
                                <th class="py-3 px-2">Email</th>
                                <th class="py-3 px-2">Role</th>
                                <th class="py-3 px-2">Registered On</th>
                                <th class="py-3 px-2 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @forelse($firefighters as $personnel)
                                <tr>
                                    <td class="py-3 px-2 font-mono font-bold text-rose-500">{{ $personnel->badge_number ?? $personnel->badge ?? $personnel->badge_id ?? $personnel->serial_number ?? 'N/A' }}</td>
                                    <td class="py-3 px-2 font-bold text-foreground">{{ $personnel->name }}</td>
                                    <td class="py-3 px-2 text-muted">{{ $personnel->email }}</td>
                                    <td class="py-3 px-2">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $personnel->hasRole('Admin') ? 'bg-rose-500/10 text-rose-500 border border-rose-500/20' : 'bg-blue-500/10 text-blue-500 border border-blue-500/20' }}">
                                            {{ ucfirst($personnel->getRoleNames()->first() ?? 'Firefighter') }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-2 font-mono text-muted">{{ $personnel->created_at->format('Y-m-d') }}</td>
                                    <td class="py-3 px-2 text-right">
                                        <button type="button" 
                                                @click="editModalOpen = true; currentPersonnel = {
                                                    id: '{{ $personnel->id }}',
                                                    name: '{{ addslashes($personnel->name) }}',
                                                    badge_number: '{{ $personnel->badge_number ?? $personnel->badge ?? $personnel->badge_id ?? $personnel->serial_number ?? '' }}',
                                                    email: '{{ $personnel->email }}',
                                                    role: '{{ $personnel->getRoleNames()->first() ?? 'Firefighter' }}',
                                                    update_url: '{{ route('admin.firefighters.update', $personnel->id) }}'
                                                }" 
                                                class="px-3 py-1 bg-secondary hover:bg-muted text-foreground font-bold rounded-lg transition cursor-pointer">
                                            Edit
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-6 text-center text-muted italic">No personnel found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>
</x-app-layout>