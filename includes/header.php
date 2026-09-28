<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : "Moj PHP Projekat"; ?></title>
    
    <!-- Tailwind Play CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- 2. Alpine.js CDN (Uvek ide sa 'defer' atributom) -->
    <script src="https://jsdelivr.net" defer></script>
    
    <!-- Opciono: Tailwind konfiguracija -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        glavna: '#3b82f6',
                    }
                }
            }
        }
    </script>
    <!--
        <script src="/js/main.js" defer></script>
    -->
    
</head>