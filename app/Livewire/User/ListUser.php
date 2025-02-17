<?php

namespace App\Livewire\User;

use App\Enums\UserStatus;
use App\Models\User;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class ListUser extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(User::query()->with(['roles', 'userProfile']))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Role')
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : ucfirst($state))
                    ->badge()
                    ->sortable(query: function ($query, $direction) {
                        $query->leftJoin('model_has_roles', 'model_has_roles.model_id', '=', 'users.id')
                            ->leftJoin('roles', 'roles.id', '=', 'model_has_roles.role_id')
                            ->orderBy('roles.name', $direction);
                    }),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_by_user_id')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->formatStateUsing(fn ($state) => UserStatus::getKey($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    ViewAction::make()
                        ->label('View Details')
                        ->mutateRecordDataUsing(function (array $data, $record): array {
                            // Load relationship data
                            $data['userProfile'] = [
                                'avatar' => $record->userProfile->avatar ?? null,
                                'phone'   => $record->userProfile->phone ?? null,
                                'address' => $record->userProfile->address ?? null,
                                'city'    => $record->userProfile->city ?? null,
                            ];
                            return $data;
                        })->infolist([
                            ImageEntry::make('userProfile.avatar')
                                ->circular(),
                            TextEntry::make('name'),
                            TextEntry::make('email')
                                ->icon('heroicon-m-envelope'),
                            TextEntry::make('userProfile.address')
                        ]),
//                        ->form([
//
//                            TextInput::make('name')
//                                ->label('Name')
//                                ->disabled(),  // Disabled để không cho phép chỉnh sửa
//                            TextInput::make('email')
//                                ->label('Email')
//                                ->disabled(),
//                            TextInput::make('userProfile.phone')
//                                ->label('Phone')
//                                ->disabled(),
//                            TextInput::make('userProfile.address')
//                                ->label('Address')
//                                ->disabled(),
//                            TextInput::make('userProfile.city')
//                                ->label('City')
//                                ->disabled(),
//                            Select::make('status')
//                                ->label('Status')
//                                ->options(UserStatus::asSelectArray())
//                                ->disabled(),  // Disabled để không cho phép chỉnh sửa
//
//                        ]),
                    EditAction::make()->form([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        DateTimePicker::make('email_verified_at'),
                        Select::make('status')
                            ->label('Status')
                            ->options(UserStatus::asSelectArray())
                            ->required(),
                    ]),
                    DeleteAction::make(),
                ])->dropdown(true),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //
                ]),
            ]);
    }

    public function render(): View
    {
        return view('livewire.user.list-user');
    }
}
