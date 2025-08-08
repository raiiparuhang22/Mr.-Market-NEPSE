<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payments;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class PaymentResource extends Resource
{
    protected static ?string $model = Payments::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Payment Management';
    protected static ?string $navigationLabel = 'Payments';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // SINGLE field for user_id — options only include "user" role users.
                Forms\Components\Select::make('user_id')
                    ->label('User')
                    ->options(fn () => User::where('user_type', 'user')
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()
                    )
                    ->default(fn () => Auth::id())
                    ->required()
                    ->searchable()
                    // If the logged in person is a normal user, disable the select (they cannot change it).
                    ->disabled(fn () => Auth::user()->user_type === 'user')
                    // Ensure disabled value still gets sent to the server:
                    ->dehydrated(),

                Forms\Components\Select::make('payment_type')
                    ->options([
                        'khalti' => 'Khalti',
                        'esewa'  => 'eSewa',
                        'bank'   => 'Bank',
                    ])
                    ->required(),

                Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->prefix('NPR')
                    ->required()
                    ->minValue(10)
                    ->maxValue(1000000)
                    ->rule('regex:/^\d+(\.\d{1,2})?$/'),

                Forms\Components\DatePicker::make('payment_date')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                            $set('next_renew_date', Carbon::parse($state)->addDays(30));
                        }
                    }),

                Forms\Components\DatePicker::make('next_renew_date')
                    ->label('Next Renewal Date')
                    ->readOnly()
                    ->dehydrated()
                    ->reactive(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                // Normal users only see their own payments
                if (Auth::user()->user_type === 'user') {
                    $query->where('user_id', Auth::id());
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('User')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('payment_type')->label('Method')->sortable(),
                Tables\Columns\TextColumn::make('amount')->money('NPR', true)->sortable(),
                Tables\Columns\TextColumn::make('payment_date')->label('Payment Date')->date(),
                Tables\Columns\TextColumn::make('next_renew_date')->label('Next Renew Date')->date(),
                Tables\Columns\TextColumn::make('created_at')->label('Created At')->dateTime('M d, Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('payment_type')
                    ->options([
                        'khalti' => 'Khalti',
                        'esewa'  => 'eSewa',
                        'bank'   => 'Bank',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) =>
                        Auth::user()->user_type === 'admin'
                        || $record->user_id === Auth::id()
                    ),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) =>
                        Auth::user()->user_type === 'admin'
                        || $record->user_id === Auth::id()
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()->user_type === 'admin'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit'   => Pages\EditPayment::route('/{record}/edit'),
        ];
    }

    /**
     * Mutate form data before create to protect against bypass:
     * - If current user is not admin, force user_id = auth()->id()
     * - If admin submitted a user_id that is an admin, reject
     */
    public static function mutateFormDataBeforeCreate(array $data): array
    {
        // Normal user — force their own ID
        if (Auth::user()->user_type === 'user') {
            $data['user_id'] = Auth::id();
        }

        // Admin — ensure they cannot create payment for another admin
        if (Auth::user()->user_type === 'admin' && isset($data['user_id'])) {
            $target = User::find($data['user_id']);
            if ($target && $target->user_type === 'admin') {
                throw ValidationException::withMessages([
                    'user_id' => 'You cannot create a payment for another admin.',
                ]);
            }
        }

        return $data;
    }

    // Also enforce same checks on update (optional but safe)
    public static function mutateFormDataBeforeSave(array $data): array
    {
        if (Auth::user()->user_type === 'user') {
            $data['user_id'] = Auth::id();
        }

        if (Auth::user()->user_type === 'admin' && isset($data['user_id'])) {
            $target = User::find($data['user_id']);
            if ($target && $target->user_type === 'admin') {
                throw ValidationException::withMessages([
                    'user_id' => 'You cannot assign a payment to another admin.',
                ]);
            }
        }

        return $data;
    }
}
