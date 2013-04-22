<?php

namespace Adis\IwatchyouBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class SearchParlamentariType extends AbstractType {
    
    public function buildForm(FormBuilderInterface $builder, array $options){
        $builder->add('nome', 'search', array('required'  => false));
        $builder->add('ramo', 'choice', array(
            'choices' => array('camera' => 'Camera', 'senato' => 'Senato'),
            'required'  => false,
            'expanded'  => false,
            'empty_value' => 'Ramo del Parlamento',
        ));
        $builder->add('regione', 'choice', array(
            'choices' => array('Abruzzo' => 'Abruzzo',
                'Basilicata' => 'Basilicata',
                'Calabria' => 'Calabria',
                'Campania' => 'Campania',
                'Emilia-Romagna' => 'Emilia-Romagna',
                'Friuli-Venezia Giulia' => 'Friuli-Venezia Giulia',
                'Lazio' => 'Lazio',
                'Liguria' => 'Liguria',
                'Lombardia' => 'Lombardia',
                'Marche' => 'Marche',
                'Molise' => 'Molise',
                'Piemonte' => 'Piemonte',
                'Puglia' => 'Puglia',
                'Sardegna' => 'Sardegna',
                'Sicilia' => 'Sicilia',
                'Toscana' => 'Toscana',
                'Trentino-Alto Adige' => 'Trentino-Alto Adige',
                'Umbria' => 'Umbria',
                'Valle d\'Aosta' => 'Valle d\'Aosta',
                'Veneto' => 'Veneto',
                'America meridionale' => 'America meridionale',
                'America settentrionale e centrale' => 'America settentrionale e centrale',
                'Asia-Africa-Oceania-Antartide' => 'Asia-Africa-Oceania-Antartide',
                'Europa' => 'Europa'),
            'required'  => false,
            'expanded'  => false,
            'empty_value' => 'Regione d\'elezione',
        ));
        $builder->add('partito', 'choice', array(
            'choices' => array('Popolo della Libertà' => 'Popolo della Libertà',
                'Scelta Civica' => 'Scelta Civica',
                'Movimento 5 Stelle' => 'Movimento 5 Stelle',
                'Partito Democratico' => 'Partito Democratico',
                'Sinistra Ecologia e Libertà' => 'Sinistra Ecologia e Libertà',
                'Autonomie' => 'Autonomie',
                'Lega Nord Padania' => 'Lega Nord Padania',
                'Fratelli d\'Italia' => 'Fratelli d\'Italia',
                'Centro Democratico' => 'Centro Democratico',
                'Gruppo Misto' => 'Gruppo Misto'),
            'required'  => false,
            'expanded'  => false,
            'empty_value' => 'Partito',
        ));
    }
    
    public function getName() {
        return 'search';
    }
}

?>
