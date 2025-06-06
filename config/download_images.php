<?php
// Create products directory if it doesn't exist
$dir = '../assets/images/products';
if (!file_exists($dir)) {
    mkdir($dir, 0777, true);
}

// Array of placeholder image URLs (using placeholder.com for demo)
$images = [
    'flask1.png' => 'https://placehold.co/600x600/333/white?text=Premium+Flask',
    'flask2.png' => 'https://placehold.co/600x600/0066cc/white?text=Sport+Bottle',
    'flask3.png' => 'https://placehold.co/600x600/000000/white?text=Travel+Mug',
    'flask4.png' => 'https://placehold.co/600x600/ff69b4/white?text=Kids+Bottle'
];

// Download each image
foreach ($images as $filename => $url) {
    $filepath = $dir . '/' . $filename;
    if (file_put_contents($filepath, file_get_contents($url))) {
        echo "Downloaded: $filename<br>";
    } else {
        echo "Failed to download: $filename<br>";
    }
}

echo "Image download completed!";
?> 