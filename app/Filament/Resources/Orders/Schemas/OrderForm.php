<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Customer;
use App\Models\QuoteRequest;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات الطلب')
                    ->description('المعلومات الأساسية للطلب.')
                    ->icon('heroicon-o-shopping-cart')
                    ->schema([
                        TextInput::make('reference')
                            ->label('رقم الطلب')
                            ->placeholder('مثال: ORD-000001')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->readOnly(
                                fn ($livewire) =>
                                    $livewire instanceof \App\Filament\Resources\Orders\Pages\EditOrder
                            ),

                        Select::make('customer_id')
                            ->label('العميل')
                            ->relationship(
                                name: 'customer',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query) =>
                                    $query
                                        ->where('is_active', true)
                                        ->orderBy('name')
                            )
                            ->getOptionLabelFromRecordUsing(
                                fn (Customer $customer): string =>
                                    "{$customer->name} — {$customer->phone}"
                            )
                            ->searchable(['name', 'phone'])
                            ->preload()
                            ->required()
                            ->native(false)
                            ->placeholder('اختر العميل')
                            ->createOptionForm([
                                Section::make('بيانات العميل الجديد')
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('اسم العميل')
                                            ->required()
                                            ->maxLength(255),

                                        TextInput::make('phone')
                                            ->label('رقم الهاتف')
                                            ->required()
                                            ->maxLength(255),

                                        TextInput::make('email')
                                            ->label('البريد الإلكتروني')
                                            ->email()
                                            ->maxLength(255),

                                        Select::make('customer_type')
                                            ->label('نوع العميل')
                                            ->options([
                                                'visitor' => 'زائر',
                                                'customer' => 'عميل',
                                                'wholesale' => 'عميل جملة',
                                            ])
                                            ->required()
                                            ->default('customer')
                                            ->native(false),

                                        Textarea::make('address')
                                            ->label('العنوان')
                                            ->rows(2),

                                        Textarea::make('notes')
                                            ->label('ملاحظات')
                                            ->rows(2),
                                    ])
                                    ->columns(2),
                            ])
                            ->createOptionUsing(
                                function (array $data): int {
                                    $customer = Customer::create([
                                        'name' => $data['name'],
                                        'phone' => $data['phone'],
                                        'email' => $data['email'] ?? null,
                                        'customer_type' => $data['customer_type'] ?? 'customer',
                                        'address' => $data['address'] ?? null,
                                        'notes' => $data['notes'] ?? null,
                                        'is_active' => true,
                                    ]);

                                    return $customer->id;
                                }
                            )
                            ->live()
                            ->afterStateUpdated(function (
                                $state,
                                callable $set
                            ): void {
                                $set('quote_request_id', null);
                            }),

                        Select::make('quote_request_id')
                            ->label('طلب التسعير')
                            ->options(function (callable $get): array {
                                $customerId = $get('customer_id');

                                if (!$customerId) {
                                    return [];
                                }

                                return QuoteRequest::query()
                                    ->where('customer_id', $customerId)
                                    ->latest()
                                    ->get()
                                    ->mapWithKeys(
                                        function (QuoteRequest $quote): array {
                                            return [
                                                $quote->id =>
                                                    "{$quote->reference} — " .
                                                    match ($quote->status) {
                                                        'new' => 'جديد',
                                                        'assigned' => 'تم التعيين',
                                                        'review' => 'قيد المراجعة',
                                                        'approved' => 'معتمد',
                                                        'rejected' => 'مرفوض',
                                                        'converted' => 'تم التحويل',
                                                        default => 'غير محدد',
                                                    },
                                            ];
                                        }
                                    )
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->native(false)
                            ->placeholder(
                                'اختر طلب التسعير المرتبط بالعميل'
                            )
                            ->disabled(
                                fn (callable $get): bool =>
                                    !$get('customer_id')
                            ),

                        Select::make('status')
                            ->label('حالة الطلب')
                            ->options([
                                'pending' => 'قيد الانتظار',
                                'confirmed' => 'مؤكد',
                                'processing' => 'قيد المعالجة',
                                'ready' => 'جاهز',
                                'completed' => 'مكتمل',
                                'cancelled' => 'ملغى',
                            ])
                            ->required()
                            ->default('pending')
                            ->native(false),

                        TextInput::make('total_amount')
                            ->label('إجمالي الطلب')
                            ->numeric()
                            ->inputMode('decimal')
                            ->minValue(0)
                            ->required()
                            ->default(0),

                        Select::make('currency')
                            ->label('العملة')
                            ->options([
                                'YER' => 'ريال يمني (YER)',
                                'USD' => 'دولار أمريكي (USD)',
                                'SAR' => 'ريال سعودي (SAR)',
                            ])
                            ->required()
                            ->default('YER')
                            ->native(false),

                        Textarea::make('notes')
                            ->label('ملاحظات الطلب')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}