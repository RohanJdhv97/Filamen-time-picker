<?php

namespace App\Filament\Resources;

use App\Filament\Filters\DateRangeFilter;
use App\Filament\Resources\SessionSlotResource\Pages;
use App\Models\Session;
use App\Models\SessionSlot;
use Filament\Forms;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class SessionSlotResource extends Resource
{
    protected static ?string $model = SessionSlot::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'slot_code';

    protected static ?string $navigationGroup = 'Session Management';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Card::make()->schema([
                Grid::make([
                    'sm' => 1,
                    'md' => 2,
                    'lg' => 2,
                ])->schema([
                            Select::make('session_id')
                                ->rules(['exists:sessions,id'])
                                ->required()
                                ->relationship('session', 'name')
                                ->options(function () {
                                    return Session::where('active', 1)->pluck('name', 'id')->toArray();
                                })
                                ->searchable()
                                ->getSearchResultsUsing(fn(string $search)
                                    => Session::where('name', 'like', "%{$search}%")
                                        ->where('active', 1)
                                        ->limit(50)
                                        ->pluck('name', 'id'))
                                ->placeholder('Session')
                                ->reactive(),

                            DatePicker::make('date')
                                ->rules(['date'])
                                ->required()
                                ->minDate(now()->toDateString())
                                ->reactive()
                                ->placeholder('Date'),

                            TimePicker::make('start_time')
                                ->afterOrEqual(function (callable $get) {
                                    if ($get('date') == 'now') {
                                        return 'now';
                                    } else {
                                        return null;
                                    }
                                })
                                ->timezone('Asia/Kolkata')
                                ->minutesStep(15)
                                ->required()
                                ->placeholder('Start Time'),

                            TimePicker::make('end_time')
                                ->rules(['date'])
                                ->minutesStep(15)
                                ->after('start_time')
                                ->required()
                                ->timezone('Asia/Kolkata')
                                ->placeholder('End Time'),

                            TextInput::make('seats')
                                ->rules(['numeric'])
                                ->required()
                                ->minValue(1)
                                ->maxValue(function (callable $get) {
                                    if ($get('session_id')) {
                                        $session_type = Session::where('id', $get('session_id'))->first()->session_type;
                                        if ($session_type == 'group') {
                                            return 999;
                                        }
                                    }
                                    return 1;
                                })
                                ->default(1)
                                ->numeric()
                                ->placeholder('Seats'),

                            Hidden::make('available_seats')
                                ->default(0),

                            Hidden::make('booked_seats')
                                ->default(0),

                            Repeater::make('online_session_details')
                                ->schema([
                                    TextInput::make('Name')
                                        ->required()
                                        ->rules(['min:3', 'max:25'])
                                        ->placeholder('Social Media'),
                                    TextInput::make('Value')
                                        ->required()
                                        ->rules(['min:3', 'max:255'])
                                        ->placeholder('Link')
                                ])
                                ->default([
                                    [
                                        'Name' => 'Zoom',
                                        'Value' => ''
                                    ],
                                    [
                                        'Name' => 'Google Meet',
                                        'Value' => ''
                                    ],
                                    [
                                        'Name' => 'Microsoft Teams',
                                        'Value' => ''
                                    ],
                                    [
                                        'Name' => 'Skype',
                                        'Value' => ''
                                    ]
                                ])
                                ->reactive()
                                ->hidden(function (callable $get) {
                                    if ($get('session_id')) {
                                        if (Session::find($get('session_id'))->session_mode == 'offline') {
                                            return true;
                                        }
                                        return false;
                                    }
                                    return true;
                                })
                                ->required()
                                ->columns(2)
                                ->columnSpan([
                                    'md' => 2,
                                    'lg' => 2,
                                ]),

                            Toggle::make('active')
                                ->rules(['boolean'])
                                ->required()
                                ->default('0'),
                        ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                TextColumn::make('slot_code')
                    ->searchable(true, null, false, true)
                    ->copyable()
                    ->placeholder('N/A')
                    ->toggleable(),
                TextColumn::make('session.name')
                    ->toggleable()
                    ->placeholder('N/A')
                    ->searchable(true, null, false, true)
                    ->limit(50),
                TextColumn::make('date')
                    ->toggleable()
                    ->placeholder('N/A')
                    ->searchable(true, null, false, true)
                    ->alignCenter()
                    ->date(),
                TextColumn::make('start_time')
                    ->toggleable()
                    ->placeholder('N/A')
                    ->searchable(true, null, false, true)
                    ->alignCenter()
                    ->time('H:i A'),
                TextColumn::make('end_time')
                    ->toggleable()
                    ->placeholder('N/A')
                    ->time('H:i A')
                    ->searchable(true, null, false, true)
                    ->alignCenter(),
                TextColumn::make('seats')
                    ->toggleable()
                    ->placeholder('N/A')
                    ->alignCenter()
                    ->searchable(true, null, false, true),
                TextColumn::make('available_seats')
                    ->toggleable()
                    ->placeholder('N/A')
                    ->alignCenter()
                    ->searchable(true, null, false, true),
                TextColumn::make('booked_seats')
                    ->toggleable()
                    ->placeholder('N/A')
                    ->alignCenter()
                    ->searchable(true, null, false, true),
                IconColumn::make('active')
                    ->toggleable()
                    ->alignCenter()
                    ->boolean(),
            ])
            ->filters([
                DateRangeFilter::make('created_at'),

                SelectFilter::make('session_id')
                    ->relationship('session', 'name')
                    ->indicator('Session')
                    ->multiple()
                    ->label('Session'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            SessionSlotResource\RelationManagers\SessionCancellationsRelationManager::class,
            SessionSlotResource\RelationManagers\ReviewRatingsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSessionSlots::route('/'),
            'create' => Pages\CreateSessionSlot::route('/create'),
            'view' => Pages\ViewSessionSlot::route('/{record}'),
            'edit' => Pages\EditSessionSlot::route('/{record}/edit'),
        ];
    }
}
