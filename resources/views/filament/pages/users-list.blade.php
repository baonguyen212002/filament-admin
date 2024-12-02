<x-filament::page>
    <div>
        <h1 class="text-xl font-bold">Users</h1>
        <table class="min-w-full bg-black">
            <thead>
                <tr>
                    <th class="px-4 py-2">ID</th>
                    <th class="px-4 py-2">Name</th>
                    <th class="px-4 py-2">Email</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users['data'] as $user)
                <tr>
                    <td class="border px-4 py-2">{{ $user['id'] }}</td>
                    <td class="border px-4 py-2">{{ $user['name'] }}</td>
                    <td class="border px-4 py-2">{{ $user['email'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-filament::page>