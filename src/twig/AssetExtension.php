<?php



namespace App\Twig;



use Twig\Extension\AbstractExtension;

use Twig\TwigFunction;

use Twig\TwigFilter;



class AssetExtension extends AbstractExtension

{

    public function getFunctions()

    {

        return [

            new TwigFunction('get_asset_url', [$this, 'getAssetUrl']),

            new TwigFunction('asset', [$this, 'getAssetUrl']),

            new TwigFunction('path', [$this, 'getPath']),

        ];

    }



    public function getFilters()

    {

        return [

            new TwigFilter('string', [$this, 'stringFilter']),

            new TwigFilter('json_decode', [$this, 'jsonDecodeFilter']),

        ];

    }

    

    public function getPath($route, $parameters = [])

    {

        // Always use HTTPS to prevent mixed content issues
        $scheme = 'https';

        $baseUrl = '';
        if (isset($_SERVER['HTTP_HOST'])) {
            $baseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];
        }
        

        // Start with the index page

        $url = $baseUrl . '/index.php?page=' . urlencode($route);

        

        // Add any additional parameters

        foreach ($parameters as $key => $value) {

            $url .= '&' . urlencode($key) . '=' . urlencode($value);

        }

        
        return $url;

    }

    

    public function stringFilter($value)

    {

        return (string) $value;

    }

    

    public function jsonDecodeFilter($value, $assoc = true)

    {

        if (is_string($value)) {

            return json_decode($value, $assoc);

        }

        return $value;

    }



    public function getAssetUrl($path, $absolute = false)
    {
        // Remove 'public/' prefix if it exists
        if (strpos($path, 'public/') === 0) {
            $path = substr($path, strlen('public/'));
        }

        // Remove leading slash if present (after potentially removing 'public/')
        if (strpos($path, '/') === 0) {
            $path = substr($path, 1);
        }
        
        // Détection de l'environnement de production
        $isProduction = isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'ledesignduweb.com';
        
        // For absolute URLs (when needed) or production environment
        if ($absolute || $isProduction) {
            // Always use HTTPS to prevent mixed content issues
            $scheme = 'https';
            
            // Construct base path with domain
            if (isset($_SERVER['HTTP_HOST'])) {
                $basePath = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/';
                return $basePath . $path;
            }
            // Fallback if HTTP_HOST is not set (e.g. CLI context)
            return '/' . $path; 
        }
        
        // For relative URLs (default) - only in development
        return '/' . $path;
    }

}
