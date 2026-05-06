<?php

namespace WILCITY_IMPORT\Register;

use Elementor\Core\Settings\Page\Model;
use Elementor\TemplateLibrary\Source_Local;

class WilcityElemtorImport extends Source_Local
{
    protected $aTerms = [
      246 => [
        'slug'     => 'destinations',
        'taxonomy' => 'listing_cat'
      ],
      247 => [
        'slug'     => 'hotels',
        'taxonomy' => 'listing_cat'
      ],
      250 => [
        'slug'     => 'shopping',
        'taxonomy' => 'listing_cat'
      ],
      380 => [
        'slug'     => 'ethnic',
        'taxonomy' => 'listing_cat'
      ],
      382 => [
        'slug'     => 'fine-dining',
        'taxonomy' => 'listing_cat'
      ],
      383 => [
        'slug'     => 'premium-casual',
        'taxonomy' => 'listing_cat'
      ],
      221 => [
        'slug'     => 'new-york',
        'taxonomy' => 'listing_location'
      ],
      515 => [
        'slug'     => 'thailand',
        'taxonomy' => 'listing_location'
      ],
      232 => [
        'slug'     => 'barcelona',
        'taxonomy' => 'listing_location'
      ],
      239 => [
        'slug'     => 'bangkok',
        'taxonomy' => 'listing_location'
      ],
      381 => [
        'slug'     => 'london',
        'taxonomy' => 'listing_location'
      ],
      543 => [
        'slug'     => 'phuket-province',
        'taxonomy' => 'listing_location'
      ],
      535 => [
        'slug'     => 'chicago',
        'taxonomy' => 'listing_location'
      ]
    ];
    protected $aNewTermIds = [];
    
    public function run($file_name, $pageID = null)
    {
        return $this->import($file_name, $pageID);
    }
    
    private function createPage($file_name)
    {
        $aParse   = explode('/', $file_name);
        $fileName = end($aParse);
        $fileName = str_replace(['.json', '-'], ['', ' '], $fileName);
        $fileName = ucfirst($fileName);
        
        $pageID = wp_insert_post(
          [
            'post_title'  => $fileName,
            'post_type'   => 'page',
            'post_status' => 'publish'
          ]
        );
        
        update_post_meta($pageID, '_wp_page_template', 'templates/page-builder.php');
        
        return $pageID;
    }
    
    private function isTemplateExist($data)
    {
        global $wpdb;
        
        return $wpdb->get_var(
          $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_title=%s AND post_type=%s",
            $data['title'],
            parent::CPT
          )
        );
    }
    
    protected function replaceOldTermIdsWithNewTermIds($content)
    {
        foreach ($this->aTerms as $oldTermId => $aInfo) {
            if (isset($this->aNewTermIds[$oldTermId])) {
                $newTermId = $this->aNewTermIds[$oldTermId];
            } else {
                $oTerm = get_term_by('slug', $aInfo['slug'], $aInfo['taxonomy']);
                if (empty($oTerm) || is_wp_error($oTerm)) {
                    $newTermId = '';
                } else {
                    $newTermId                     = $oTerm->term_id;
                    $this->aNewTermIds[$oldTermId] = $oTerm->term_id;
                }
            }
            
            $content = str_replace('"'.$oldTermId.'"', '"'.$newTermId.'"', $content);
        }
        
        return $content;
    }
    
    private function import($file_name, $pageID = null)
    {
        $rawContent = file_get_contents($file_name);
        $rawContent = $this->replaceOldTermIdsWithNewTermIds($rawContent);
        $data       = json_decode($rawContent, true);
        
        if (empty($data)) {
            return new \WP_Error('file_error', 'Invalid File.');
        }
        
        $content = $data['content'];
        
        if (!is_array($content)) {
            return new \WP_Error('file_error', 'Invalid File.');
        }
        
        if (!$template_id = $this->isTemplateExist($data)) {
            $content = $this->process_export_import_content($content, 'on_import');
            
            $page_settings = [];
            
            if (!empty($data['page_settings'])) {
                $page = new Model([
                  'id'       => 0,
                  'settings' => $data['page_settings'],
                ]);
                
                $page_settings_data = $this->process_element_export_import_content($page, 'on_import');
                
                if (!empty($page_settings_data['settings'])) {
                    $page_settings = $page_settings_data['settings'];
                }
            }
            
            $template_id = $this->save_item([
              'content'       => $content,
              'title'         => $data['title'],
              'type'          => $data['type'],
              'page_settings' => $page_settings,
            ]);
            
            if (is_wp_error($template_id)) {
                return false;
            }
        }
        
        if (empty($pageID)) {
            $pageID = $this->createPage($file_name);
        }
        
        if (isset($data['content'])) {
            update_post_meta($pageID, '_elementor_data', $data['content']);
        }
        update_post_meta($pageID, '_elementor_edit_mode', 'builder');
        update_post_meta($pageID, '_elementor_version', $data['version']);
        
        return true;
    }
}
