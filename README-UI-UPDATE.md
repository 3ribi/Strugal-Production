# Mise à jour UI — STRUGAL Production (thème TailAdmin)

Copiez ces fichiers par-dessus votre projet (mêmes chemins), en gardant votre `.env` et votre base de données.

1. **Supprimez** ces anciens fichiers (remplacés) :
   - `resources/views/dashboard.blade.php`
   - `app/Livewire/Departments/Laquage/CreateReport.php`
   - `resources/views/components/departments/laquage/create-report.blade.php`
2. Dans votre `.env`, mettez : `APP_LOCALE=fr`, `APP_FALLBACK_LOCALE=fr`, `APP_NAME="Strugal Production"`.
3. Lancez :

   ```
   npm install        # ajoute apexcharts (graphiques)
   npm run build      # ou npm run dev
   php artisan optimize:clear
   ```

Aucune migration n'est nécessaire.

Le logo est provisoire (une lettre « S ») : remplacez-le dans `resources/views/components/ui/brand.blade.php`.
