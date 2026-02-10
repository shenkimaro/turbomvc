<?php

class FileLbr {
	//************************************************************************************************************************\\

	/**
	 * 
	 * @param string $fileName Caminho completo para o arquivo, no caso de upload pode ser usado o $_FILES['file']['tmp_name']
	 * @return string
	 */
	public static function getType($fileName) {
		if (class_exists('finfo')) {
			$finfo = new finfo(FILEINFO_MIME_TYPE);
			if (is_object($finfo)) {
				$mime_type = $finfo->file($fileName);
				if ($mime_type == 'inode/x-empty' && pathinfo($fileName, PATHINFO_EXTENSION) == 'pdf') {
					return 'application/pdf';
				}
				return $mime_type;
			}
		}

		if (pathinfo($fileName, PATHINFO_EXTENSION) == 'pdf') {
			return 'application/pdf';
		}

		if (pathinfo($fileName, PATHINFO_EXTENSION) == 'jpg' || pathinfo($fileName, PATHINFO_EXTENSION) == 'jpeg') {
			return 'image/jpeg';
		}

		if (pathinfo($fileName, PATHINFO_EXTENSION) == 'png') {
			return 'image/png';
		}

		if (pathinfo($fileName, PATHINFO_EXTENSION) == 'gif') {
			return 'image/gif';
		}
		return 'inode/x-empty';
	}

	/**
	 * 
	 * @param array|string $myfiles
	 * @param string $dir
	 * @param int $quality
	 * @param string $rename
	 * @param int $dimensionMax
	 * @param int $qtdeDisp
	 * @return array Os nomes dos arquivos criados
	 * @throws Exception
	 */
	public static function uploadImg($myfiles, string $dir, int $quality = 100, string $rename = '', int $dimensionMax = 0, int $qtdeDisp = 28): array {
		if (!is_dir($dir)) {
			$createdDirectory = mkdir("$dir", 0777,true);
			if(!$createdDirectory){
				throw new Exception('Nao foi possivel criar o diretorio');
			}
		}
		//transforma uma imagem simples em um array de imagens com uma entrada
		if (is_string($myfiles['name'])) {
			foreach ($myfiles as $key => $value) {
				$file[$key][0] = $value;
			}
			$myfiles = $file;
		}
		if (count($myfiles) >= $qtdeDisp) {
			throw new Exception("Nem todas as imagens foram carregadas. O número máximo de $qtdeDisp imagens foi atingido. ");
		}
		$f_nameArray = $myfiles['name'];
		$filename = [];
		for ($i = 0; $i < count($f_nameArray); $i++) {
			$f_name = Conversor::somenteAlfabetoNumeros($myfiles['name'][$i], '');
			$f_tmp = $myfiles['tmp_name'][$i];
			$f_type = $myfiles['type'][$i];
			$f_size = $myfiles['size'][$i];
			Image::validateImage($f_type, $f_size);

			$extension = Image::getExtensionFromFileName($f_name);
			$tmp = Image::imageCreate($f_tmp, $extension);
			
			//se deseja mudar o nome do arquivo, deve ser informado em rename.
			if ($rename != "") {
				$f_name = $rename . "." . $extension;
			}

			//local onde sera salvo e o nome do arquivo
			$filename[$i] = $dir . $f_name;
			$tmp = static::redimencionar($tmp, $f_tmp, $dimensionMax);
			//cria a imagem na pasta e depois remove as infos nao mais necessarias
			Image::saveImg($tmp, $filename[$i], $quality);
		}
		return $filename;
	}

	private static function redimencionar($img, $filename, $dimensionMax = 0) {
		$imgSize = getimagesize($filename);
		if($dimensionMax > 0){
			if($imgSize[0] >= $imgSize[1]){
				$newwidth = $dimensionMax;
				$proporcao = $dimensionMax * 100 / $imgSize[0];
				$newheight = ($proporcao * 0.01) * $imgSize[1];
			}else if($imgSize[1] > $imgSize[0]){
				$newheight = $dimensionMax;
				$proporcao = $dimensionMax * 100 / $imgSize[1];
				$newwidth = ($proporcao * 0.01) * $imgSize[0];
			}
			$dst = imagecreatetruecolor($newwidth, $newheight);
			imagecopyresampled($dst, $img, 0, 0, 0, 0, $newwidth, $newheight, $imgSize[0], $imgSize[1]);
			$img = $dst;
		}
		return $img;
	}

}
