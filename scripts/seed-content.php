#!/usr/bin/env php
<?php

/**
 * @file
 * Script d'import des données de démonstration SNIJ.
 *
 * Usage: drush php:script scripts/seed-content.php
 */

use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

// Load JSON data
$data_file = __DIR__ . '/../data/documents.json';
if (!file_exists($data_file)) {
  echo "Error: Data file not found: $data_file\n";
  return;
}

$data = json_decode(file_get_contents($data_file), TRUE);
if (!$data || !isset($data['documents'])) {
  echo "Error: Invalid JSON data\n";
  return;
}

echo "Starting SNIJ content import...\n\n";

// First, create taxonomy terms if they don't exist
$domaines = [
  'القانون الدستوري' => ['fr' => 'Droit constitutionnel', 'en' => 'Constitutional Law'],
  'القانون الإداري' => ['fr' => 'Droit administratif', 'en' => 'Administrative Law'],
  'القانون الجبائي' => ['fr' => 'Droit fiscal', 'en' => 'Tax Law'],
  'قانون الشغل' => ['fr' => 'Droit du travail', 'en' => 'Labor Law'],
  'القانون التجاري' => ['fr' => 'Droit commercial', 'en' => 'Commercial Law'],
  'القانون الجزائي' => ['fr' => 'Droit pénal', 'en' => 'Criminal Law'],
  'القانون المدني' => ['fr' => 'Droit civil', 'en' => 'Civil Law'],
  'قانون الأسرة' => ['fr' => 'Droit de la famille', 'en' => 'Family Law'],
  'قانون البيئة' => ['fr' => 'Droit de l\'environnement', 'en' => 'Environmental Law'],
  'قانون الأعمال' => ['fr' => 'Droit des affaires', 'en' => 'Business Law'],
  'القانون الاجتماعي' => ['fr' => 'Droit social', 'en' => 'Social Law'],
];

echo "Creating taxonomy terms...\n";

foreach ($domaines as $ar_name => $translations) {
  // Check if term already exists
  $existing = \Drupal::entityTypeManager()
    ->getStorage('taxonomy_term')
    ->loadByProperties([
      'vid' => 'domaine_juridique',
      'name' => $ar_name,
    ]);

  if (empty($existing)) {
    $term = Term::create([
      'vid' => 'domaine_juridique',
      'name' => $ar_name,
      'langcode' => 'ar',
    ]);
    $term->save();

    // Add translations
    if ($term->hasTranslation('fr')) {
      $term->removeTranslation('fr');
    }
    $term->addTranslation('fr', ['name' => $translations['fr']]);

    if ($term->hasTranslation('en')) {
      $term->removeTranslation('en');
    }
    $term->addTranslation('en', ['name' => $translations['en']]);
    $term->save();

    echo "  Created term: $ar_name\n";
  } else {
    echo "  Term exists: $ar_name\n";
  }
}

echo "\nCreating documents...\n";

// Import documents
foreach ($data['documents'] as $doc) {
  // Check if document already exists by numero
  $existing = \Drupal::entityTypeManager()
    ->getStorage('node')
    ->loadByProperties([
      'type' => $doc['type'],
      'field_numero' => $doc['numero'],
    ]);

  if (!empty($existing)) {
    echo "  Skipping (exists): {$doc['numero']}\n";
    continue;
  }

  // Find the taxonomy term
  $terms = \Drupal::entityTypeManager()
    ->getStorage('taxonomy_term')
    ->loadByProperties([
      'vid' => 'domaine_juridique',
      'name' => $doc['domaine_ar'],
    ]);
  $term = reset($terms);

  // Create the node
  $node_data = [
    'type' => $doc['type'],
    'langcode' => 'ar',
    'title' => $doc['title_ar'],
    'field_numero' => $doc['numero'],
    'field_date_promulgation' => $doc['date'],
    'field_jort_reference' => $doc['jort_reference'] ?? '',
    'field_contenu' => [
      'value' => $doc['content_ar'],
      'format' => 'full_html',
    ],
    'field_resume_ia' => [
      'value' => $doc['ai_summary_ar'] ?? '',
      'format' => 'full_html',
    ],
    'field_domaine' => $term ? ['target_id' => $term->id()] : NULL,
    'field_statut' => $doc['statut'] ?? 'en_vigueur',
    'status' => 1,
  ];

  $node = Node::create($node_data);
  $node->save();

  // Add French translation
  if (!empty($doc['title_fr'])) {
    $node->addTranslation('fr', [
      'title' => $doc['title_fr'],
      'field_contenu' => [
        'value' => $doc['content_fr'] ?? '',
        'format' => 'full_html',
      ],
      'field_resume_ia' => [
        'value' => $doc['ai_summary_fr'] ?? '',
        'format' => 'full_html',
      ],
    ]);
    $node->save();
  }

  echo "  Created: [{$doc['type']}] {$doc['numero']} - {$doc['title_ar']}\n";
}

echo "\n=== Import completed! ===\n";
echo "Total documents in file: " . count($data['documents']) . "\n";
