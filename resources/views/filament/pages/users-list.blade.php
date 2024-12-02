<x-filament::page>
    <div>
        <h1 class="text-xl font-bold">Users</h1>
        <table class="min-w-full bg-black">
            <thead>
                <tr>
                    <th class="px-4 py-2">ID</th>
                    <th class="px-4 py-2">Name</th>
                    <th class="px-4 py-2">Email</th>
                    <th class="px-4 py-2">Role</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users['data'] as $user)
                <tr>
                    <td class="border px-4 py-2">{{ $user['id'] }}</td>
                    <td class="border px-4 py-2">{{ $user['name'] }}</td>
                    <td class="border px-4 py-2">{{ $user['email'] }}</td>
                    <td class="border px-4 py-2">
                        <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">
                            {{ $user['role']['name'] ?? 'No Role' }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-filament::page>