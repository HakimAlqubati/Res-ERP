<?php

namespace App\Filament\Clusters\HRAttenanceCluster\Resources\UserDevices\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Fieldset;
use App\Models\Branch;
use App\Models\BranchArea;
use App\Models\User;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Set;

class UserDeviceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Fieldset::make('Device Information')
                ->columns(4)
                ->schema([

                    Grid::make()->columnSpanFull()->columns(4)
                        ->schema([
                            Select::make('branch_id')
                                ->label('Branch')
                                ->options(fn() => Branch::active()->pluck('name', 'id')->toArray())
                                ->searchable()
                                ->required()
                                ->columnSpan(2)
                                ->reactive()
                                ->afterStateUpdated(function (Set $set, $state) {
                                    // clear user when branch changes
                                    $set('user_id', null);
                                }),

                            Select::make('branch_area_id')
                                ->label('Branch Area')
                                ->options(function (callable $get) {
                                    $branchId = $get('branch_id');
                                    if (!$branchId) {
                                        return [];
                                    }
                                    return BranchArea::query()
                                        ->where('branch_id', $branchId)
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->toArray();
                                })
                                ->searchable()
                                ->preload()
                                ->columnSpan(2)
                                ->reactive()
                                ->nullable() // اختياري
                                ->disabled(fn(callable $get) => empty($get('branch_id'))),
                        ]),

                    Select::make('user_id')
                        ->label('User')
                        ->options(function (callable $get) {
                            $branchId = $get('branch_id');
                            if (!$branchId) {
                                return [];
                            }
                            $query = User::query()->select('id', 'name')
                                ->where('active', 1)->orWhere('active', null);
                            if ($branchId) {
                                $query->where('branch_id', $branchId);
                            }
                            return $query->limit(100)->pluck('name', 'id')->toArray();
                        })
                        ->searchable()
                        ->preload()
                    // ->hiddenOn('create')
                    ,

                    TextInput::make('device_hash')
                        ->label('Device Hash (SHA-256)')
                        // ->disabled()
                        // ->dehydrated(false)
                        ->copyable(),

                    Select::make('plat_form')
                        ->label('Platform')
                        ->options([
                            'android' => 'Android',
                        ])
                        ->native(false)
                        ->searchable()->default('android')
                        ->placeholder('Select platform')
                        ->columnSpan(1)
                        ->disabled(),

                    Toggle::make('active')
                        ->label('Active')
                        ->inline(false),

                    // DateTimePicker::make('last_login')
                    //     ->label('Last Login')
                    //     ->seconds(false)
                    //     ->displayFormat('Y-m-d H:i')
                    //     ->disabled()
                    //     ->hiddenOn('create')
                    //     ->dehydrated(false)
                    //     ->columnSpan(2),
                ])

                ->columnSpanFull(),

            Fieldset::make('Notes')
                ->schema([
                    Textarea::make('notes')
                        ->label('Notes')
                        ->placeholder('Enter any notes...')
                        ->rows(4)
                        ->autosize()
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }
}
