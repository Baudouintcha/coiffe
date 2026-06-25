const sharp = require('sharp');
const fs = require('fs');
const path = require('path');

const inputDir = './imgid';
const outputDir = './imgid/optimized';

// Créer le dossier de sortie s'il n'existe pas
if (!fs.existsSync(outputDir)) fs.mkdirSync(outputDir);

fs.readdirSync(inputDir).forEach(file => {
    // Ne traiter que les images
    if (/\.(jpg|jpeg|png|webp)$/i.test(file)) {
        sharp(path.join(inputDir, file))
            .resize(1920) // Largeur standard web
            .webp({ quality: 80 }) // Format WebP, 80% de qualité
            .toFile(path.join(outputDir, file.split('.')[0] + '.webp'))
            .then(() => console.log(`Optimisé : ${file}`))
            .catch(err => console.error(`Erreur sur ${file}:`, err));
    }
});