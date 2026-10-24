<?php

namespace App\Support;

/**
 * Single source of truth for the structure of a Laquage report.
 *
 * A report has "header" fields (one value per report) and "row" fields (one value
 * per production line). Everything that needs to know about those fields reads them
 * from here: validation, mass-assignment, casts, the forms, the detail page and the
 * Excel export.
 *
 * To add a new production column:
 *   1. add it to row() below (label, type, rules, Excel column, section),
 *   2. add a migration for the `laquage_report_lines` table,
 *   3. extend the Excel template if it needs a new column.
 * Nothing else has to change.
 */
final class LaquageFields
{
    /**
     * Report-level fields.
     *
     * `excel` is the cell of the template that receives the value; `unit` is only
     * displayed next to the value.
     *
     * @return array<string, array{label: string, type: string, rules: list<string>, excel: string, unit?: string}>
     */
    public static function header(): array
    {
        return [
            'report_date' => ['label' => 'Date du rapport', 'type' => 'date', 'rules' => ['required', 'date'], 'excel' => 'B3'],
            'line' => ['label' => 'Ligne de laquage', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:255'], 'excel' => 'B2'],
            'work_period' => ['label' => 'Période de travail', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:255'], 'excel' => 'D2'],
            'operators' => ['label' => 'Opérateurs', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:1000'], 'excel' => 'F2'],
            'micrometer_check' => ['label' => 'Vérification micromètre', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:255'], 'excel' => 'K2'],
            'brillometer' => ['label' => 'Brillomètre', 'type' => 'number', 'rules' => ['nullable', 'numeric'], 'excel' => 'M2'],
            'phase' => ['label' => 'Phase', 'type' => 'text', 'rules' => ['nullable', 'string', 'max:255'], 'excel' => 'O2'],
            'furnace_temperature' => ['label' => 'Température du four', 'type' => 'number', 'rules' => ['nullable', 'numeric'], 'excel' => 'O3', 'unit' => '°C'],
        ];
    }

    /**
     * Production-line fields, in the order of the Excel columns.
     *
     * `excel` is the column letter in the template (rows start at row 6),
     * `section` groups the inputs in the line form, `cast` is the Eloquent cast and
     * `decimal` allows decimal values in the number input.
     *
     * @return array<string, array{label: string, type: string, rules: list<string>, excel: string, section: string, cast?: string, decimal?: bool}>
     */
    public static function row(): array
    {
        $text = ['nullable', 'string', 'max:255'];
        $count = ['nullable', 'integer', 'min:0'];
        $number = ['nullable', 'numeric'];

        return [
            'source_cart' => ['label' => 'Nº CHAR. SOURCE', 'type' => 'text', 'rules' => $text, 'excel' => 'A', 'section' => 'Identification'],
            'order_number' => ['label' => 'COMMANDE', 'type' => 'text', 'rules' => $text, 'excel' => 'B', 'section' => 'Identification'],
            'of_number' => ['label' => 'O.F', 'type' => 'text', 'rules' => $text, 'excel' => 'C', 'section' => 'Identification'],
            'reference' => ['label' => 'RÉFÉRENCE', 'type' => 'text', 'rules' => $text, 'excel' => 'D', 'section' => 'Identification'],
            'bars_count' => ['label' => 'Nº BARRES', 'type' => 'number', 'rules' => $count, 'excel' => 'E', 'section' => 'Quantités', 'cast' => 'integer'],
            'cart_number' => ['label' => 'Nº CHAR', 'type' => 'text', 'rules' => $text, 'excel' => 'F', 'section' => 'Identification'],
            'rejected' => ['label' => 'REJET', 'type' => 'number', 'rules' => $count, 'excel' => 'G', 'section' => 'Quantités', 'cast' => 'integer'],
            'thickness_min' => ['label' => 'MIN.', 'type' => 'number', 'rules' => $number, 'excel' => 'H', 'section' => 'Mesures', 'cast' => 'decimal:2', 'decimal' => true],
            'thickness_max' => ['label' => 'MAX.', 'type' => 'number', 'rules' => $number, 'excel' => 'I', 'section' => 'Mesures', 'cast' => 'decimal:2', 'decimal' => true],
            'color' => ['label' => 'COULEUR', 'type' => 'text', 'rules' => $text, 'excel' => 'J', 'section' => 'Aspect'],
            'supplier' => ['label' => 'FOURNISSEUR', 'type' => 'text', 'rules' => $text, 'excel' => 'K', 'section' => 'Aspect'],
            'category' => ['label' => 'CAT.', 'type' => 'text', 'rules' => $text, 'excel' => 'L', 'section' => 'Aspect'],
            'brilliance_average' => ['label' => 'MOYENNE', 'type' => 'number', 'rules' => $number, 'excel' => 'M', 'section' => 'Mesures', 'cast' => 'decimal:2', 'decimal' => true],
            'aspect' => ['label' => 'ASPECT', 'type' => 'text', 'rules' => $text, 'excel' => 'N', 'section' => 'Aspect'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function headerKeys(): array
    {
        return array_keys(self::header());
    }

    /**
     * @return list<string>
     */
    public static function rowKeys(): array
    {
        return array_keys(self::row());
    }

    /**
     * Validation rules for the report header, optionally prefixed (e.g. "report.").
     *
     * @return array<string, list<string>>
     */
    public static function headerRules(string $prefix = ''): array
    {
        $rules = [];

        foreach (self::header() as $field => $definition) {
            $rules[$prefix.$field] = $definition['rules'];
        }

        return $rules;
    }

    /**
     * Validation rules for a production line, optionally prefixed (e.g. "lineForm.").
     *
     * @return array<string, list<string>>
     */
    public static function rowRules(string $prefix = ''): array
    {
        $rules = [];

        foreach (self::row() as $field => $definition) {
            $rules[$prefix.$field] = $definition['rules'];
        }

        return $rules;
    }

    /**
     * Eloquent casts of the production-line fields.
     *
     * @return array<string, string>
     */
    public static function rowCasts(): array
    {
        $casts = [];

        foreach (self::row() as $field => $definition) {
            if (isset($definition['cast'])) {
                $casts[$field] = $definition['cast'];
            }
        }

        return $casts;
    }

    /**
     * Row fields grouped by form section, keeping the definition order.
     *
     * @return array<string, list<string>>
     */
    public static function sections(): array
    {
        $sections = [];

        foreach (self::row() as $field => $definition) {
            $sections[$definition['section']][] = $field;
        }

        return $sections;
    }

    /**
     * Row fields that accept decimal values.
     *
     * @return list<string>
     */
    public static function decimalKeys(): array
    {
        return array_keys(array_filter(self::row(), fn (array $definition): bool => $definition['decimal'] ?? false));
    }

    /**
     * An empty (all blank) production line, as used by the line form.
     *
     * @return array<string, string>
     */
    public static function emptyRow(): array
    {
        return array_fill_keys(self::rowKeys(), '');
    }

    /**
     * Keep only the header fields of $source, turning blank strings into null.
     *
     * @param  array<string, mixed>  $source
     * @return array<string, mixed>
     */
    public static function cleanHeader(array $source): array
    {
        return self::clean(self::headerKeys(), $source);
    }

    /**
     * Keep only the production-line fields of $source, turning blank strings into null.
     *
     * @param  array<string, mixed>  $source
     * @return array<string, mixed>
     */
    public static function cleanRow(array $source): array
    {
        return self::clean(self::rowKeys(), $source);
    }

    /**
     * @param  list<string>  $keys
     * @param  array<string, mixed>  $source
     * @return array<string, mixed>
     */
    private static function clean(array $keys, array $source): array
    {
        $clean = [];

        foreach ($keys as $key) {
            $value = $source[$key] ?? null;
            $clean[$key] = $value === '' ? null : $value;
        }

        return $clean;
    }
}
