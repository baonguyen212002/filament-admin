<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;

class UsersList extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.users-list';

    public function mount()
    {
        $response = Http::post('http://shino-dev.local/graphql', [
            'query' => '
                query u{
  users(first: 10, page: 1) {
    paginatorInfo {
      count
      currentPage
      firstItem
      hasMorePages
      lastItem
      lastPage
      perPage
      total
    }
    data {
      id
      name
      email
      email_verified_at
    }
  }
}
            ',
        ]);

        $this->users = $response->json('data.users');
    }

    public $users = [];
}
