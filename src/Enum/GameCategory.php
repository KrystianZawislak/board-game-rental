<?php 

namespace App\Enum;

enum GameCategory: string 
{
    case STRATEGY = 'strategy';   
    case FAMILY   = 'family';     
    case PARTY    = 'party';      
    case CARD     = 'card';      
    case COOP     = 'coop';      
    case ECONOMIC = 'economic';  
}

