<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Récupérer une valeur par sa clé
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        return Cache::remember("setting.{$key}", 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Définir une valeur
     */
    public static function set(string $key, ?string $value): void
    {
        self::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting.{$key}");
        Cache::forget('settings.etablissement');
    }

    /**
     * Récupérer tous les paramètres de l'établissement
     */
    public static function getEtablissement(): array
    {
        return Cache::remember('settings.etablissement', 3600, function () {
            $settings = self::where('key', 'LIKE', 'etablissement_%')->pluck('value', 'key')->toArray();

            return [
                'nom' => $settings['etablissement_nom'] ?? 'Hopital de Menontin',
                'pays' => $settings['etablissement_pays'] ?? 'Republique du Benin',
                'adresse' => $settings['etablissement_adresse'] ?? '',
                'telephone' => $settings['etablissement_telephone'] ?? '',
                'email' => $settings['etablissement_email'] ?? '',
                'directeur' => $settings['etablissement_directeur'] ?? '',
                // IFU : valeur par défaut (IFU de l'hôpital) si non renseignée dans Admin > Établissement.
                'ifu' => ($settings['etablissement_ifu'] ?? '') ?: '6201200887407',
            ];
        });
    }
}
