<?php

// Ensure we have the Composer autoloader
require_once __DIR__ . '/../../lib/vendor/autoload.php';

class upload {

    private $db;
    private $profile_picture_dir;
    private $upload_dir;
    private $products_dir;
    private $message_attachments_dir;

    public function __construct($db) {
        $this->db = $db;
        
        // Load configuration parameters
        require_once __DIR__ . '/../../config/parametres.php';
        
        // Define upload directories with server root
        $this->upload_dir = $_SERVER['DOCUMENT_ROOT'] . UPLOAD_BASE_DIR;
        $this->profile_picture_dir = $_SERVER['DOCUMENT_ROOT'] . PROFILE_PICTURES_DIR;
        $this->products_dir = $_SERVER['DOCUMENT_ROOT'] . PRODUCTS_IMAGES_DIR;
        $this->message_attachments_dir = $_SERVER['DOCUMENT_ROOT'] . MESSAGE_ATTACHMENTS_DIR;
        
        // Ensure directories exist
        $this->ensureDirectoryExists($this->upload_dir);
        $this->ensureDirectoryExists($this->profile_picture_dir);
        $this->ensureDirectoryExists($this->products_dir);
        
    }
    
    /**
     * Ensures a directory exists, creates it if it doesn't
     *
     * @param string $dir Directory path
     * @return void
     */
    private function ensureDirectoryExists($dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
    }
    
    /**
     * Upload a profile picture and save locally
     *
     * @param array $file The uploaded file ($_FILES['field_name'])
     * @param int $userId User ID
     * @return array Result status and message
     */
    public function uploadProfilePicture($file, $userId) {
        // Check if file was uploaded correctly
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'message' => 'Erreur lors du téléchargement: ' . $this->getUploadErrorMessage($file['error'])
            ];
        }
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $mimeType = $this->getMimeType($file['tmp_name'], $file['name']);
        
        if (!in_array($mimeType, $allowedTypes)) {
            return [
                'success' => false,
                'message' => 'Type de fichier non autorisé. Utilisez JPG, PNG, GIF ou WEBP.'
            ];
        }
        
        // Validate file size (max size defined in config)
        if ($file['size'] > UPLOAD_MAX_SIZE) {
            return [
                'success' => false,
                'message' => 'Le fichier est trop volumineux. Taille maximale: 2 Mo.'
            ];
        }
        
        try {
            // Generate unique filename based on user ID and timestamp
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'user_' . $userId . '_' . time() . '.' . $extension;
            $filePath = $this->profile_picture_dir . $filename;
            
            // Try to create a resized version of the image
            $resized = $this->resizeImage(
                $file['tmp_name'], 
                $filePath, 
                PROFILE_PICTURE_WIDTH ?? 250, 
                PROFILE_PICTURE_HEIGHT ?? 250
            );
            
            // If resizing fails, move the uploaded file directly
            if (!$resized) {
                if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                    return [
                        'success' => false,
                        'message' => 'Erreur lors du déplacement du fichier téléchargé'
                    ];
                }
            }
            
            // Get and delete previous profile picture if it exists
            $stmt = $this->db->prepare("SELECT profile_picture FROM utilisateurs WHERE id = :id");
            $stmt->execute([':id' => $userId]);
            $oldPicture = $stmt->fetchColumn();
            
            if ($oldPicture && strpos($oldPicture, '/public/images/uploads/profile_pictures/') !== false) {
                // Try different ways to get the full path
                $oldPicturePath = $_SERVER['DOCUMENT_ROOT'] . $oldPicture;
                if (file_exists($oldPicturePath)) {
                    @unlink($oldPicturePath);
                }
                
                // Try alternative path with dirname(__FILE__)
                $rootPath = dirname(dirname(dirname(__FILE__))); // Go up 3 levels from class_upload.php
                $altPath = $rootPath . $oldPicture;
                if (file_exists($altPath)) {
                    @unlink($altPath);
                }
                
                // Log deletion attempt for debugging
                error_log("Attempted to delete old profile picture: $oldPicture");
            }
            
            // Create URL for the database (relative to document root)
            $pictureUrl = PROFILE_PICTURES_DIR . $filename;
            
            // Update user profile with the image URL
            $stmt = $this->db->prepare("UPDATE utilisateurs SET profile_picture = :url WHERE id = :id");
            $stmt->execute([
                ':url' => $pictureUrl,
                ':id' => $userId
            ]);
            
            return [
                'success' => true,
                'message' => 'Photo de profil mise à jour avec succès',
                'url' => $pictureUrl
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors du téléchargement: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Upload a product image to the server
     *
     * @param array $file The uploaded file ($_FILES['field_name'])
     * @param int $productId Product ID
     * @return array Result status and message
     */
    public function uploadProductImage($file, $productId) {
        // Check if file was uploaded correctly
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'message' => 'Erreur lors du téléchargement: ' . $this->getUploadErrorMessage($file['error'])
            ];
        }
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $mimeType = $this->getMimeType($file['tmp_name'], $file['name']);
        
        if (!in_array($mimeType, $allowedTypes)) {
            return [
                'success' => false,
                'message' => 'Type de fichier non autorisé. Utilisez JPG, PNG, GIF ou WEBP.'
            ];
        }
        
        // Validate file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            return [
                'success' => false,
                'message' => 'Le fichier est trop volumineux. Taille maximale: 5 Mo.'
            ];
        }
        
        try {
            // Generate a unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'product_' . $productId . '_' . time() . '.' . $extension;
            
            // Ensure products directory exists
            $this->ensureDirectoryExists($this->products_dir);
            
            $filePath = $this->products_dir . $filename;
            
            // Move the uploaded file to the target directory
            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                return [
                    'success' => false,
                    'message' => 'Erreur lors du déplacement du fichier téléchargé'
                ];
            }
            
            // Update product with the image URL
            $imageUrl = PRODUCTS_IMAGES_DIR . $filename;
            $stmt = $this->db->prepare("UPDATE produits SET image_url = :url WHERE id = :id");
            $stmt->execute([
                ':url' => $imageUrl,
                ':id' => $productId
            ]);
            
            return [
                'success' => true,
                'message' => 'Image de produit téléchargée avec succès',
                'url' => $imageUrl
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors du téléchargement de l\'image: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Upload an attachment for messages
     *
     * @param array $file The uploaded file ($_FILES['field_name'])
     * @param int $conversationId Conversation ID
     * @return array Result status with filename and filepath
     */
    public function uploadMessageAttachment($file, $conversationId) {
        // Check if file was uploaded correctly
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'message' => 'Erreur lors du téléchargement: ' . $this->getUploadErrorMessage($file['error'])
            ];
        }
        
        // Validate file size (max 5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            return [
                'success' => false,
                'message' => 'Le fichier est trop volumineux. Taille maximale: 5 Mo.'
            ];
        }
        
        try {
            // Create conversation attachments directory if it doesn't exist
            $conversationDir = $this->message_attachments_dir . $conversationId . '/';
            $this->ensureDirectoryExists($_SERVER['DOCUMENT_ROOT'] . $conversationDir);
            
            // Generate unique filename
            $originalFilename = basename($file['name']);
            $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
            $filename = 'message_' . time() . '_' . uniqid() . '.' . $extension;
            $filePath = $_SERVER['DOCUMENT_ROOT'] . $conversationDir . $filename;
            
            // Move the uploaded file
            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                return [
                    'success' => false,
                    'message' => 'Erreur lors du déplacement du fichier téléchargé'
                ];
            }
            
            // Create URL for database (relative to document root)
            $fileUrl = $conversationDir . $filename;
            
            return [
                'success' => true,
                'message' => 'Pièce jointe téléchargée avec succès',
                'url' => $fileUrl,
                'filename' => $originalFilename,
                'filepath' => $fileUrl
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur lors du téléchargement: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Resize an image maintaining aspect ratio
     * Falls back to simply copying the file if GD is not available
     *
     * @param string $sourcePath Path to the source image
     * @param string $destPath Path where the resized image will be saved
     * @param int $width Target width
     * @param int $height Target height
     * @return bool True if resizing was successful, false otherwise
     */
    private function resizeImage($sourcePath, $destPath, $width, $height) {
        // Check if GD extension is available
        if (!extension_loaded('gd')) {
            // GD not available, just copy the file as is
            return copy($sourcePath, $destPath);
        }
        
        // Get image info
        $info = @getimagesize($sourcePath);
        if (!$info) {
            return copy($sourcePath, $destPath); // Fallback to copy if getimagesize fails
        }
        
        $mime = $info['mime'];
        
        // Create source image based on mime type
        $source = null;
        switch ($mime) {
            case 'image/jpeg':
                $source = @imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $source = @imagecreatefrompng($sourcePath);
                break;
            case 'image/gif':
                $source = @imagecreatefromgif($sourcePath);
                break;
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    $source = @imagecreatefromwebp($sourcePath);
                }
                break;
        }
        
        if (!$source) {
            return copy($sourcePath, $destPath); // Fallback to copy if source creation fails
        }
        
        try {
            // Get original dimensions
            $originalWidth = imagesx($source);
            $originalHeight = imagesy($source);
            
            // Calculate dimensions while maintaining aspect ratio
            $ratio = min($width / $originalWidth, $height / $originalHeight);
            $newWidth = round($originalWidth * $ratio);
            $newHeight = round($originalHeight * $ratio);
            
            // Create a new image with the target dimensions
            $destination = imagecreatetruecolor($newWidth, $newHeight);
            if (!$destination) {
                imagedestroy($source);
                return copy($sourcePath, $destPath); // Fallback if destination creation fails
            }
            
            // Preserve transparency for PNG and GIF
            if ($mime == 'image/png' || $mime == 'image/gif') {
                imagealphablending($destination, false);
                imagesavealpha($destination, true);
                $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
                imagefilledrectangle($destination, 0, 0, $newWidth, $newHeight, $transparent);
            }
            
            // Resize the image
            imagecopyresampled($destination, $source, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
            
            // Save the resized image
            $result = false;
            switch ($mime) {
                case 'image/jpeg':
                    $result = imagejpeg($destination, $destPath, 90); // 90% quality
                    break;
                case 'image/png':
                    $result = imagepng($destination, $destPath, 9); // 0-9 compression level
                    break;
                case 'image/gif':
                    $result = imagegif($destination, $destPath);
                    break;
                case 'image/webp':
                    if (function_exists('imagewebp')) {
                        $result = imagewebp($destination, $destPath, 90);
                    }
                    break;
            }
            
            // Free memory
            imagedestroy($source);
            imagedestroy($destination);
            
            if (!$result) {
                return copy($sourcePath, $destPath); // Fallback if saving fails
            }
            
            return $result;
        } catch (Exception $e) {
            // Clean up resources in case of exception
            if (isset($source)) {
                imagedestroy($source);
            }
            if (isset($destination)) {
                imagedestroy($destination);
            }
            
            // Fallback to copy
            return copy($sourcePath, $destPath);
        }
    }
    
    /**
     * Get error message for upload error code
     *
     * @param int $errorCode Error code from $_FILES['field_name']['error']
     * @return string Error message
     */
    private function getUploadErrorMessage($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'Le fichier dépasse la taille maximale autorisée.';
            case UPLOAD_ERR_FORM_SIZE:
                return 'Le fichier dépasse la taille maximale autorisée par le formulaire.';
            case UPLOAD_ERR_PARTIAL:
                return 'Le fichier n\'a été que partiellement téléchargé.';
            case UPLOAD_ERR_NO_FILE:
                return 'Aucun fichier n\'a été téléchargé.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Dossier temporaire manquant.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Échec de l\'écriture du fichier sur le disque.';
            case UPLOAD_ERR_EXTENSION:
                return 'Une extension PHP a arrêté le téléchargement du fichier.';
            default:
                return 'Erreur inconnue lors du téléchargement.';
        }
    }
    
    /**
     * Get MIME type of a file using different methods
     * Falls back to extension-based detection if fileinfo is not available
     *
     * @param string $filePath Path to the file
     * @param string $fileName Original file name (for extension detection fallback)
     * @return string MIME type
     */
    private function getMimeType($filePath, $fileName) {
        $mimeType = null;
        
        // First try with fileinfo extension
        if (extension_loaded('fileinfo') && function_exists('finfo_open')) {
            try {
                $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($fileInfo, $filePath);
                finfo_close($fileInfo);
                
                // If we got a valid mime type, return it
                if ($mimeType && $mimeType !== 'application/octet-stream') {
                    return $mimeType;
                }
            } catch (Exception $e) {
                // Silently fail and move to the next method
            }
        }
        
        // If fileinfo is not available or failed, try using the getimagesize function for images
        if (function_exists('getimagesize')) {
            try {
                $imageInfo = @getimagesize($filePath);
                if ($imageInfo && isset($imageInfo['mime'])) {
                    return $imageInfo['mime'];
                }
            } catch (Exception $e) {
                // Silently fail and move to the next method
            }
        }
        
        // Last resort: use file extension to guess MIME type
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $mime_types = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp'
        ];
        
        return isset($mime_types[$extension]) ? $mime_types[$extension] : 'application/octet-stream';
    }
}
?>
