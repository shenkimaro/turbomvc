<?php

class DTOVazio extends DTO {
        //ATENÇÃO
	//esta classe é para utilizar um DTO que NÃO utiliza 
        //automaticamente os dados da requisição, como a DTO comum faz
	

	function __construct($array = null) {
		if (is_array($array)) {
			$this->setDTO($array);
		} 
	}

	
}

