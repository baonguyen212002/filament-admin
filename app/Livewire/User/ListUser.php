<?php

namespace App\Livewire\User;

use App\Enums\UserStatus;
use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
                    ->formatStateUsing(fn($state) => is_array($state) ? implode(', ', $state) : ucfirst($state))
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
                    ->formatStateUsing(fn($state) => UserStatus::getKey($state))
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
                                'phone' => $record->userProfile->phone ?? null,
                                'address' => $record->userProfile->address ?? null,
                                'city' => $record->userProfile->city ?? null,
                            ];
                            return $data;
                        })->infolist([
                            ImageEntry::make('userProfile.avatar')
                                ->circular(),
                            TextEntry::make('name'),
                            TextEntry::make('email')
                                ->icon('heroicon-o-envelope'),
                            TextEntry::make('userProfile.phone')
                                ->label('Phone')->icon('heroicon-o-phone'),
                            TextEntry::make('userProfile.address')
                                ->label('Address')->icon('heroicon-o-map-pin'),
                            TextEntry::make('userProfile.city')
                                ->label('City')->icon('heroicon-o-map'),
                        ]),
                    EditAction::make()
                        ->mutateRecordDataUsing(function (array $data, User $record): array {
                            // Gán dữ liệu userProfile vào form

                            $data['userProfile'] = [
                                'avatar'  => $record->userProfile->avatar ?? null,
                                'phone'   => $record->userProfile->phone ?? null,
                                'address' => $record->userProfile->address ?? null,
                                'city'    => $record->userProfile->city ?? null,
                            ];
                            return $data;
                        })
                        ->mutateFormDataUsing(function (array $data, User $record): array {
                            // 🖼 Kiểm tra nếu có file avatar được upload
                            $data['id'] = $record->id;
//                            if (!empty($data['userProfile']['avatar']) && $data['userProfile']['avatar'] instanceof \Livewire\TemporaryUploadedFile) {
//                                $uploadedFile = $data['userProfile']['avatar'];
//                                $avatarUrl = uploadImageToGraphQL($uploadedFile); // Gửi file lên GraphQL API
//                                Log::debug('$avatarUrl:0'.json_encode($avatarUrl));
//                                if ($avatarUrl) {
//                                    $data['userProfile']['avatar'] = $avatarUrl; // Gán URL mới từ API
//                                } else {
//                                    throw new \Exception('Upload ảnh thất bại, vui lòng thử lại.');
//                                }
//                            }

                            return $data;
                        })
                        ->using(function (array $data, User $record, EditAction $action) {
                            // Biến chứa avatar URL (nếu có upload)
                            $avatarUrl = $record->userProfile->avatar ?? null;
                            // Kiểm tra nếu có upload ảnh mới
//                            if (!empty($data['userProfile']['avatar'])) {
//                                // Lấy đường dẫn file từ Livewire (chứa file tạm)
//                                $uploadedFilePath = storage_path('app/livewire-tmp/' . $data['userProfile']['avatar']);
//
//                                if (file_exists($uploadedFilePath)) {
//                                    // Mở file & đọc nội dung
//                                    $fileContent = file_get_contents($uploadedFilePath);
//                                    $fileName = basename($uploadedFilePath);
//
//                                    // 🛠 Gửi file lên API GraphQL theo chuẩn Multipart
//                                    $uploadResponse = Http::withHeaders([
//                                        'Authorization' => 'Bearer ' . session('accessToken'),
//                                    ])->attach(
//                                        '1', // Key "1" trùng với mapping
//                                        $fileContent,
//                                        $fileName
//                                    )->post(env('GRAPHQL_ENDPOINT'), [
//                                        'operations' => json_encode([
//                                            'operationName' => 'fileImageUpload',
//                                            'variables' => ['input' => ['image' => null]],
//                                            'query' => '
//                    mutation fileImageUpload($input: FileImageUploadInput!) {
//                        fileImageUpload(input: $input) {
//                            url
//                            originalFilename
//                        }
//                    }
//                ',
//                                        ]),
//                                        'map' => json_encode(['1' => ['variables.input.image']]), // Map file với biến "image"
//                                    ]);
//
//                                    // 📌 Kiểm tra phản hồi từ API
//                                    $uploadResult = $uploadResponse->json();
//                                    Log::debug('GraphQL Upload Response: ' . json_encode($uploadResult));
//
//                                    if (isset($uploadResult['data']['fileImageUpload']['url'])) {
//                                        // 🖼 Lấy URL ảnh từ API GraphQL
//                                        $avatarUrl = $uploadResult['data']['fileImageUpload']['url'];
//                                        Log::debug('Uploaded avatar URL: ' . $avatarUrl);
//                                    } else {
//                                        Log::error('Upload error: ' . json_encode($uploadResult['errors'] ?? 'Unknown error'));
//                                    }
//                                }
//                            }


                            // Chuẩn bị data cho userUpdate
                            $userUpdateData = [
                                'id' => $data['id'],
                                'name' => $data['name'],
                                'avatar' => $avatarUrl, // Gán avatar mới nếu có
                                'phone' => $data['userProfile']['phone'] ?? null,
                                'address' => $data['userProfile']['address'] ?? null,
                                'city' => $data['userProfile']['city'] ?? null,

                            ];
                            // Gọi mutation userUpdate
                            $updateResponse = Http::withHeaders([
                                'Authorization' => 'Bearer ' . session('accessToken'),
                                'Content-Type' => 'application/json',
                            ])->post(env('GRAPHQL_ENDPOINT'), [
                                'query' => '
                                    mutation userUpdate($input: UserUpdateInput!) {
                                        userUpdate(input: $input) {
                                            id
                                            name
                                            email
                                            userProfile {
                                                avatar
                                                phone
                                                address
                                                city
                                            }
                                        }
                                    }
                                ',
                                'variables' => ['input' => $userUpdateData],
                            ]);

                            $updateResult = $updateResponse->json();

                            if (isset($updateResult['errors'])) {
                                return Notification::make()
                                    ->title('Cập nhật thất bại!')
                                    ->danger()
                                    ->body($updateResult['errors'][0]['message'] ?? 'Có lỗi xảy ra.')
                                    ->send();
                            }

                            return Notification::make()
                                ->title('Cập nhật thành công!')
                                ->success()
                                ->send();
                        })
                        ->form([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('email')
                                ->email()
                                ->disabled()
                                ->maxLength(255),
                            Select::make('status')
                                ->label('Status')
                                ->options(UserStatus::asSelectArray())
                                ->required(),
                            FileUpload::make('userProfile.avatar')
                                ->image()
                                ->imageEditor(),
                            TextInput::make('userProfile.phone')
                                ->label('Phone')
                                ->maxLength(20),
                            TextInput::make('userProfile.address')
                                ->label('Address')
                                ->maxLength(255),
                            TextInput::make('userProfile.city')
                                ->label('City')
                                ->maxLength(100),
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
