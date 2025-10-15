<?php

/**
 * @file
 * View template for the DNA occurrence edit form.
 *
 * Indicia, the OPAL Online Recording Toolkit.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/gpl.html.
 *
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */

warehouse::loadHelpers(['data_entry_helper']);
$id = $values['dna_occurrence:id'] ?? NULL;
?>

<form action="<?php echo url::site() . 'dna_occurrence/save' ?>" method="post" id="entry_form">

<fieldset>
    <legend>DNA details</legend>
<?php
  echo data_entry_helper::hidden_text([
    'fieldname' => 'dna_occurrence:id',
    'default' => $id,
  ]);
  echo data_entry_helper::hidden_text([
    'fieldname' => 'dna_occurrence:occurrence_id',
    'default' => $values['dna_occurrence:occurrence_id'] ?? NULL,
  ]);
  // Return to the Occurrence view tab.
  echo data_entry_helper::hidden_text([
    'fieldname' => 'return_url',
    'default' => 'occurrence/edit/' . $values['dna_occurrence:occurrence_id'] ?? NULL,
  ]);
  echo data_entry_helper::textarea([
    'fieldname' => 'dna_occurrence:associated_sequences',
    'label' => 'Associated sequences',
    'default' => str_replace([',', ';'], "\n", $values['dna_occurrence:associated_sequences'] ?? ''),
    'helpText' => 'A list (one per line) of identifiers (publication, global unique identifier, URI) of genetic sequence information associated with the record.',
  ]);
  echo data_entry_helper::textarea([
    'fieldname' => 'dna_occurrence:dna_sequence',
    'label' => 'DNA sequence',
    'default' => $values['dna_occurrence:dna_sequence'] ?? NULL,
    'helpText' => 'The DNA sequence.',
    'required' => TRUE,
  ]);
  echo data_entry_helper::text_input([
    'fieldname' => 'dna_occurrence:target_gene',
    'label' => 'Target gene',
    'default' => $values['dna_occurrence:target_gene'] ?? NULL,
    'helpText' => 'Targeted gene or marker name for marker-based studies.',
    'required' => TRUE,
  ]);
  echo data_entry_helper::text_input([
    'fieldname' => 'dna_occurrence:pcr_primer_reference',
    'label' => 'PCR primer reference',
    'default' => $values['dna_occurrence:pcr_primer_reference'] ?? NULL,
    'helpText' => 'Reference for the primers.',
    'required' => TRUE,
  ]);
  echo data_entry_helper::text_input([
    'fieldname' => 'dna_occurrence:env_medium',
    'label' => 'Environmental medium',
    'default' => $values['dna_occurrence:pcr_primer_reference'] ?? NULL,
    'helpText' => 'The environmental medium which surrounded your sample or specimen prior to sampling. Should be a subclass of an ENVO material.',
  ]);
  echo data_entry_helper::text_input([
    'fieldname' => 'dna_occurrence:env_broad_scale',
    'label' => 'Broad-scale environment',
    'default' => $values['dna_occurrence:env_broad_scale'] ?? NULL,
    'helpText' => "The broad-scale environment the sample or specimen came from. Subclass of ENVO's biome class.",
  ]);
  echo data_entry_helper::text_input([
    'fieldname' => 'dna_occurrence:otu_db',
    'label' => 'OTU database',
    'default' => $values['dna_occurrence:otu_db'] ?? NULL,
    'helpText' => 'The OTU database (i.e. sequences not generated as part of the current study) used to assigning taxonomy to OTUs or ASVs.',
  ]);
  echo data_entry_helper::text_input([
    'fieldname' => 'dna_occurrence:otu_seq_comp_appr',
    'label' => 'OTU sequence comparison approach',
    'default' => $values['dna_occurrence:otu_seq_comp_appr'] ?? NULL,
    'helpText' => 'The OTU sequence comparison approach, such as tools and thresholds used to assign “species-level” names to OTUs or ASVs.',
  ]);
  echo data_entry_helper::text_input([
    'fieldname' => 'dna_occurrence:otu_class_appr',
    'label' => 'OTU classification approach',
    'default' => $values['dna_occurrence:otu_class_appr'] ?? NULL,
    'helpText' => 'The OTU classification approach / algorithm and clustering level (if relevant) when defining OTUs or ASVs.',
  ]);
  echo data_entry_helper::text_input([
    'fieldname' => 'dna_occurrence:env_local_scale',
    'label' => 'Local-scale environment',
    'default' => $values['dna_occurrence:env_local_scale'] ?? NULL,
    'helpText' => 'The local environmental context the sample or specimen came from. Please use terms that are present in ENVO and which are of smaller spatial grain than your entry for env_broad_scale.',
  ]);
  echo data_entry_helper::text_input([
    'fieldname' => 'dna_occurrence:target_subfragment',
    'label' => 'Target subfragment',
    'default' => $values['dna_occurrence:target_subfragment'] ?? NULL,
    'helpText' => 'Name of subfragment of a gene or marker.',
  ]);
  echo data_entry_helper::text_input(options: [
    'fieldname' => 'dna_occurrence:pcr_primer_name_forward',
    'label' => 'Forward PCR primer name',
    'default' => $values['dna_occurrence:pcr_primer_name_forward'] ?? NULL,
    'helpText' => 'Name of the forward PCR primer that were used to amplify the sequence of the targeted gene, locus or subfragment.',
  ]);
  echo data_entry_helper::text_input(options: [
    'fieldname' => 'dna_occurrence:pcr_primer_forward',
    'label' => 'Forward PCR primer',
    'default' => $values['dna_occurrence:pcr_primer_forward'] ?? NULL,
    'helpText' => 'Forward PCR primer that was used to amplify the sequence of the targeted gene, locus or subfragment.',
  ]);
  echo data_entry_helper::text_input(options: [
    'fieldname' => 'dna_occurrence:pcr_primer_name_reverse',
    'label' => 'Reverse PCR primer name',
    'default' => $values['dna_occurrence:pcr_primer_name_reverse'] ?? NULL,
    'helpText' => 'Name of the reverse PCR primer that was used to amplify the sequence of the targeted gene, locus or subfragment.',
  ]);
  echo data_entry_helper::text_input(options: [
    'fieldname' => 'dna_occurrence:pcr_primer_reverse',
    'label' => 'Reverse PCR primer',
    'default' => html::initial_value( $values, 'dna_occurrence:pcr_primer_reverse'),
    'helpText' => 'Reverse PCR primer that was used to amplify the sequence of the targeted gene, locus or subfragment.',
  ]);
  echo html::form_buttons($id !== NULL, FALSE, FALSE);
  data_entry_helper::enable_validation('entry_form');
  echo data_entry_helper::dump_javascript();
?>
  </fieldset>
</form>
