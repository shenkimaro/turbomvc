<?php

/* * *****************************************************************************
 * Software: FPDF                                                               *
 * Version:  1.53                                                               *
 * Date:     2004-12-31                                                         *
 * Author:   Olivier PLATHEY                                                    *
 * License:  Freeware                                                           *
 *                                                                              *
 * You may use and modify this software as you wish.                            *
 * ***************************************************************************** */
define('tFPDF_VERSION', '1.24');
define('FPDF_VERSION', '1.53');
if (!defined('FPDF_FONTPATH')) {
	define('FPDF_FONTPATH', __DIR__.'/tfpdf/font/');
}	

class FPDF {

	var $unifontSubset;
	var $page;			   // current page number
	var $n;				  // current object number
	var $offsets;			// array of object offsets
	var $buffer;			 // buffer holding in-memory PDF
	var $pages;			  // array containing pages
	var $state;			  // current document state
	var $compress;		   // compression flag
	var $k;				  // scale factor (number of points in user unit)
	var $DefOrientation;	 // default orientation
	var $CurOrientation;	 // current orientation
	var $StdPageSizes;	   // standard page sizes
	var $DefPageSize;		// default page size
	var $CurPageSize;		// current page size
	var $PageSizes;		  // used for pages with non default sizes or orientations
	var $wPt, $hPt;		  // dimensions of current page in points
	var $w, $h;			  // dimensions of current page in user unit
	var $lMargin;			// left margin
	var $tMargin;			// top margin
	var $rMargin;			// right margin
	var $bMargin;			// page break margin
	var $cMargin;			// cell margin
	var $x, $y;			  // current position in user unit
	var $lasth;			  // height of last printed cell
	var $LineWidth;		  // line width in user unit
	var $fontpath;		   // path containing fonts
	var $CoreFonts;		  // array of core font names
	var $fonts;			  // array of used fonts
	var $FontFiles;		  // array of font files
	var $diffs;			  // array of encoding differences
	var $FontFamily;		 // current font family
	var $FontStyle;		  // current font style
	var $underline;		  // underlining flag
	var $CurrentFont;		// current font info
	var $FontSizePt;		 // current font size in points
	var $FontSize;		   // current font size in user unit
	var $DrawColor;		  // commands for drawing color
	var $FillColor;		  // commands for filling color
	var $TextColor;		  // commands for text color
	var $ColorFlag;		  // indicates whether fill and text colors are different
	var $ws;				 // word spacing
	var $images;			 // array of used images
	var $PageLinks;		  // array of links in pages
	var $links;			  // array of internal links
	var $AutoPageBreak;	  // automatic page breaking
	var $PageBreakTrigger;   // threshold used to trigger page breaks
	var $InHeader;		   // flag set when processing header
	var $InFooter;		   // flag set when processing footer
	var $ZoomMode;		   // zoom display mode
	var $LayoutMode;		 // layout display mode
	var $title;			  // title
	var $subject;			// subject
	var $author;			 // author
	var $keywords;		   // keywords
	var $creator;			// creator
	var $AliasNbPages;	   // alias for total number of pages
	var $PDFVersion;		 // PDF version number
	
	const FONT_BANDAL = 'bandal';
	const FONT_COURIER = 'cour';
	const FONT_DEJA_VU_SANS = 'dejavusans';	
	const FONT_DEJA_VU_SANS_MONO = 'dejavusansmono';	
	const FONT_DEJA_VU_SERIF = 'dejavuserif';	
	const FONT_EUNJIN = 'eunjin';
	const FONT_HELVETICA = 'helvetica';		
	const FONT_MERRIWEATHER = 'merriweather';		
	const FONT_ROBOTO = 'roboto-regular';
	const FONT_TIMES = 'times';

	/*	 * *****************************************************************************
	 *                                                                              *
	 *                               Public methods                                 *
	 *                                                                              *
	 * ***************************************************************************** */

	function __construct($orientation = 'P', $unit = 'mm', $size = 'A4') {
		// Some checks
		$this->_dochecks();
		// Initialization of properties
		$this->page = 0;
		$this->n = 2;
		$this->buffer = '';
		$this->pages = array();
		$this->PageSizes = array();
		$this->state = 0;
		$this->fonts = array();
		$this->FontFiles = array();
		$this->diffs = array();
		$this->images = array();
		$this->links = array();
		$this->InHeader = false;
		$this->InFooter = false;
		$this->lasth = 0;
		$this->FontFamily = '';
		$this->FontStyle = '';
		$this->FontSizePt = 12;
		$this->underline = false;
		$this->DrawColor = '0 G';
		$this->FillColor = '0 g';
		$this->TextColor = '0 g';
		$this->ColorFlag = false;
		$this->ws = 0;
		// Font path
		if (defined('FPDF_FONTPATH')) {
			$this->fontpath = FPDF_FONTPATH;
			if (substr($this->fontpath, -1) != '/' && substr($this->fontpath, -1) != '\\')
				$this->fontpath .= '/';
		} elseif (is_dir(dirname(__FILE__) . '/font'))
			$this->fontpath = dirname(__FILE__) . '/font/';
		else
			$this->fontpath = '';
		// Core fonts
		$this->CoreFonts = array(
			PDF::FONT_BANDAL, 
			PDF::FONT_DEJA_VU_SANS,
			PDF::FONT_DEJA_VU_SANS_MONO,
			PDF::FONT_DEJA_VU_SERIF,
			PDF::FONT_EUNJIN,
			PDF::FONT_HELVETICA, 			 			 
			PDF::FONT_MERRIWEATHER,
			PDF::FONT_ROBOTO,
            PDF::FONT_COURIER
			);
		// Scale factor
		if ($unit == 'pt')
			$this->k = 1;
		elseif ($unit == 'mm')
			$this->k = 72 / 25.4;
		elseif ($unit == 'cm')
			$this->k = 72 / 2.54;
		elseif ($unit == 'in')
			$this->k = 72;
		else
			$this->Error('Incorrect unit: ' . $unit);
		// Page sizes
		$this->StdPageSizes = array('a3' => array(841.89, 1190.55), 'a4' => array(595.28, 841.89), 'a5' => array(420.94, 595.28),
			'letter' => array(612, 792), 'legal' => array(612, 1008));
		$size = $this->_getpagesize($size);
		$this->DefPageSize = $size;
		$this->CurPageSize = $size;
		// Page orientation
		$orientation = strtolower($orientation);
		if ($orientation == 'p' || $orientation == 'portrait') {
			$this->DefOrientation = 'P';
			$this->w = $size[0];
			$this->h = $size[1];
		} elseif ($orientation == 'l' || $orientation == 'landscape') {
			$this->DefOrientation = 'L';
			$this->w = $size[1];
			$this->h = $size[0];
		} else
			$this->Error('Incorrect orientation: ' . $orientation);
		$this->CurOrientation = $this->DefOrientation;
		$this->wPt = $this->w * $this->k;
		$this->hPt = $this->h * $this->k;
		// Page margins (1 cm)
		$margin = 28.35 / $this->k;
		$this->SetMargins($margin, $margin);
		// Interior cell margin (1 mm)
		$this->cMargin = $margin / 10;
		// Line width (0.2 mm)
		$this->LineWidth = .567 / $this->k;
		// Automatic page break
		$this->SetAutoPageBreak(true, 2 * $margin);
		// Default display mode
		$this->SetDisplayMode('default');
		// Enable compression
		$this->SetCompression(true);
		// Set default PDF version number
		$this->PDFVersion = '1.3';
	}

	function SetTextColorArray($var = array(0, 0, 0)) {
		//Set color for text
		if(!is_array($var)){
			return;
		}
		if (($var[0] == 0 && $var[1] == 0 && $var[2] == 0) || $var[1] == -1)
			$this->TextColor = sprintf('%.3f g', $var[0] / 255);
		else
			$this->TextColor = sprintf('%.3f %.3f %.3f rg', $var[0] / 255, $var[1] / 255, $var[2] / 255);
		$this->ColorFlag = ($this->FillColor != $this->TextColor);
	}

	function SetMargins($left, $top, $right = null) {
		// Set left, top and right margins
		$this->lMargin = $left;
		$this->tMargin = $top;
		if ($right === null)
			$right = $left;
		$this->rMargin = $right;
	}

	function SetLeftMargin($margin) {
		// Set left margin
		$this->lMargin = $margin;
		if ($this->page > 0 && $this->x < $margin)
			$this->x = $margin;
	}

	function SetTopMargin($margin) {
		// Set top margin
		$this->tMargin = $margin;
	}

	function SetRightMargin($margin) {
		// Set right margin
		$this->rMargin = $margin;
	}

	function SetAutoPageBreak($auto, $margin = 0) {
		// Set auto page break mode and triggering margin
		$this->AutoPageBreak = $auto;
		$this->bMargin = $margin;
		$this->PageBreakTrigger = $this->h - $margin;
	}


    function getPageBreakTrigger() {
        return $this->PageBreakTrigger;
    }

	function SetDisplayMode($zoom, $layout = 'default') {
		// Set display mode in viewer
		if ($zoom == 'fullpage' || $zoom == 'fullwidth' || $zoom == 'real' || $zoom == 'default' || !is_string($zoom))
			$this->ZoomMode = $zoom;
		else
			$this->Error('Incorrect zoom display mode: ' . $zoom);
		if ($layout == 'single' || $layout == 'continuous' || $layout == 'two' || $layout == 'default')
			$this->LayoutMode = $layout;
		else
			$this->Error('Incorrect layout display mode: ' . $layout);
	}

	function SetCompression($compress) {
		// Set page compression
		if (function_exists('gzcompress'))
			$this->compress = $compress;
		else
			$this->compress = false;
	}

	function SetTitle($title, $isUTF8 = true) {
		// Title of document
		if ($isUTF8)
			$title = $this->_UTF8toUTF16($title);
		$this->title = $title;
	}

	function SetSubject($subject, $isUTF8 = true) {
		// Subject of document
		if ($isUTF8)
			$subject = $this->_UTF8toUTF16($subject);
		$this->subject = $subject;
	}

	function SetAuthor($author, $isUTF8 = true) {
		// Author of document
		if ($isUTF8)
			$author = $this->_UTF8toUTF16($author);
		$this->author = $author;
	}

	function SetKeywords($keywords, $isUTF8 = true) {
		// Keywords of document
		if ($isUTF8)
			$keywords = $this->_UTF8toUTF16($keywords);
		$this->keywords = $keywords;
	}

	function SetCreator($creator, $isUTF8 = true) {
		// Creator of document
		if ($isUTF8)
			$creator = $this->_UTF8toUTF16($creator);
		$this->creator = $creator;
	}

	function AliasNbPages($alias = '{nb}') {
		// Define an alias for total number of pages
		$this->AliasNbPages = $alias;
	}

	function Error($msg) {
		// Fatal error
		die('<b>FPDF error:</b> ' . $msg);
	}

	function Open() {
		// Begin document
		$this->state = 1;
	}

	function Close() {
		// Terminate document
		if ($this->state == 3)
			return;
		if ($this->page == 0)
			$this->AddPage();
		// Page footer
		$this->InFooter = true;
		$this->Footer();
		$this->InFooter = false;
		// Close page
		$this->_endpage();
		// Close document
		$this->_enddoc();
	}

	function AddPage($orientation = '', $size = '') {
		// Start a new page
		if ($this->state == 0)
			$this->Open();
		$family = $this->FontFamily;
		$style = $this->FontStyle . ($this->underline ? 'U' : '');
		$fontsize = $this->FontSizePt;
		$lw = $this->LineWidth;
		$dc = $this->DrawColor;
		$fc = $this->FillColor;
		$tc = $this->TextColor;
		$cf = $this->ColorFlag;
		if ($this->page > 0) {
			// Page footer
			$this->InFooter = true;
			$this->Footer();
			$this->InFooter = false;
			// Close page
			$this->_endpage();
		}
		// Start new page
		$this->_beginpage($orientation, $size);
		// Set line cap style to square
		$this->_out('2 J');
		// Set line width
		$this->LineWidth = $lw;
		$this->_out(sprintf('%.2F w', $lw * $this->k));
		// Set font
		if ($family)
			$this->SetFont($family, $style, $fontsize);
		// Set colors
		$this->DrawColor = $dc;
		if ($dc != '0 G')
			$this->_out($dc);
		$this->FillColor = $fc;
		if ($fc != '0 g')
			$this->_out($fc);
		$this->TextColor = $tc;
		$this->ColorFlag = $cf;
		// Page header
		$this->InHeader = true;
		$this->Header();
		$this->InHeader = false;
		// Restore line width
		if ($this->LineWidth != $lw) {
			$this->LineWidth = $lw;
			$this->_out(sprintf('%.2F w', $lw * $this->k));
		}
		// Restore font
		if ($family)
			$this->SetFont($family, $style, $fontsize);
		// Restore colors
		if ($this->DrawColor != $dc) {
			$this->DrawColor = $dc;
			$this->_out($dc);
		}
		if ($this->FillColor != $fc) {
			$this->FillColor = $fc;
			$this->_out($fc);
		}
		$this->TextColor = $tc;
		$this->ColorFlag = $cf;
	}

	function Header() {
		// To be implemented in your own inherited class
	}

	function Footer() {
		// To be implemented in your own inherited class
	}

	function PageNo() {
		// Get current page number
		return $this->page;
	}

	function SetDrawColor($r, $g = null, $b = null) {
		// Set color for all stroking operations
		if (($r == 0 && $g == 0 && $b == 0) || $g === null)
			$this->DrawColor = sprintf('%.3F G', $r / 255);
		else
			$this->DrawColor = sprintf('%.3F %.3F %.3F RG', $r / 255, $g / 255, $b / 255);
		if ($this->page > 0)
			$this->_out($this->DrawColor);
	}

	function SetFillColor($r, $g = null, $b = null) {
		// Set color for all filling operations
		if (($r == 0 && $g == 0 && $b == 0) || $g === null)
			$this->FillColor = sprintf('%.3F g', $r / 255);
		else
			$this->FillColor = sprintf('%.3F %.3F %.3F rg', $r / 255, $g / 255, $b / 255);
		$this->ColorFlag = ($this->FillColor != $this->TextColor);
		if ($this->page > 0)
			$this->_out($this->FillColor);
	}

	function SetTextColor($r, $g = null, $b = null) {
		// Set color for text
		if (($r == 0 && $g == 0 && $b == 0) || $g === null)
			$this->TextColor = sprintf('%.3F g', $r / 255);
		else
			$this->TextColor = sprintf('%.3F %.3F %.3F rg', $r / 255, $g / 255, $b / 255);
		$this->ColorFlag = ($this->FillColor != $this->TextColor);
	}

	function GetStringWidth($s) {
		// Get width of a string in the current font
		$s = (string) $s;
		$cw = &$this->CurrentFont['cw'];
		$w = 0;
		if ($this->unifontSubset) {
			$unicode = $this->UTF8StringToArray($s);
			foreach ($unicode as $char) {
				if (isset($cw[$char])) {
					$w += (ord($cw[2 * $char]) << 8) + ord($cw[2 * $char + 1]);
				} else if ($char > 0 && $char < 128 && isset($cw[chr($char)])) {
					$w += $cw[chr($char)];
				} else if (isset($this->CurrentFont['desc']['MissingWidth'])) {
					$w += $this->CurrentFont['desc']['MissingWidth'];
				} else if (isset($this->CurrentFont['MissingWidth'])) {
					$w += $this->CurrentFont['MissingWidth'];
				} else {
					$w += 500;
				}
			}
		} else {
			$l = mb_strlen($s);
			for ($i = 0; $i < $l; $i++)
				$w += $cw[$s[$i]];
		}
		return $w * $this->FontSize / 1000;
	}

	function SetLineWidth($width) {
		// Set line width
		$this->LineWidth = $width;
		if ($this->page > 0)
			$this->_out(sprintf('%.2F w', $width * $this->k));
	}

	function Line($x1, $y1, $x2, $y2) {
		// Draw a line
		$this->_out(sprintf('%.2F %.2F m %.2F %.2F l S', $x1 * $this->k, ($this->h - $y1) * $this->k, $x2 * $this->k, ($this->h - $y2) * $this->k));
	}

	function Rect($x, $y, $w, $h, $style = '') {
		// Draw a rectangle
		if ($style == 'F')
			$op = 'f';
		elseif ($style == 'FD' || $style == 'DF')
			$op = 'B';
		else
			$op = 'S';
		$this->_out(sprintf('%.2F %.2F %.2F %.2F re %s', $x * $this->k, ($this->h - $y) * $this->k, $w * $this->k, -$h * $this->k, $op));
	}

	function AddFont($family, $style = '', $file = '', $uni = true) {
		// Add a TrueType, OpenType or Type1 font
		$family = strtolower($family);
		$style = strtoupper($style);
		if ($style == 'IB')
			$style = 'BI';
		if ($file == '') {
			if ($uni) {
				$file = str_replace(' ', '', $family) . strtolower($style) . '.ttf';
			} else {
				$file = str_replace(' ', '', $family) . strtolower($style) . '.php';
			}
		}
		$fontkey = $family . $style;
		if (isset($this->fonts[$fontkey]))
			return;

		if ($uni) {
			if (defined("_SYSTEM_TTFONTS") && file_exists(_SYSTEM_TTFONTS . $file)) {
				$ttffilename = _SYSTEM_TTFONTS . $file;
			} else {
				$ttffilename = $this->_getfontpath() . 'unifont/' . $file;
			}
			$unifilename = $this->_getfontpath() . 'unifont/' . strtolower(substr($file, 0, (strpos($file, '.'))));
			$name = '';
			$originalsize = 0;
			
			if (file_exists($unifilename . '.mtx.php')) {
				include($unifilename . '.mtx.php');
			}
			$ttfstat = stat($ttffilename);
			if (!isset($type) || !isset($name) || $originalsize != $ttfstat['size']) {
				$ttffile = $ttffilename;
				require_once($this->_getfontpath() . 'unifont/ttfonts.php');
				$ttf = new TTFontFile();
				$ttf->getMetrics($ttffile);
				$cw = $ttf->charWidths;		
				$name = preg_replace('/[ ()]/', '', $ttf->fullName);

				$desc = array('Ascent' => round($ttf->ascent),
					'Descent' => round($ttf->descent),
					'CapHeight' => round($ttf->capHeight),
					'Flags' => $ttf->flags,
					'FontBBox' => '[' . round($ttf->bbox[0]) . " " . round($ttf->bbox[1]) . " " . round($ttf->bbox[2]) . " " . round($ttf->bbox[3]) . ']',
					'ItalicAngle' => $ttf->italicAngle,
					'StemV' => round($ttf->stemV),
					'MissingWidth' => round($ttf->defaultWidth));
				$up = round($ttf->underlinePosition);
				$ut = round($ttf->underlineThickness);
				$originalsize = $ttfstat['size'] + 0;
				$type = 'TTF';
				// Generate metrics .php file
				$s = '<?php' . "\n";
				$s .= '$name=\'' . $name . "';\n";
				$s .= '$type=\'' . $type . "';\n";
				$s .= '$desc=' . var_export($desc, true) . ";\n";
				$s .= '$up=' . $up . ";\n";
				$s .= '$ut=' . $ut . ";\n";
				$s .= '$ttffile=\'' . $ttffile . "';\n";
				$s .= '$originalsize=' . $originalsize . ";\n";
				$s .= '$fontkey=\'' . $fontkey . "';\n";
				$s .= "?>";
				if (is_writable(dirname($this->_getfontpath() . 'unifont/' . 'x'))) {
					$fh = fopen($unifilename . '.mtx.php', "w");
					fwrite($fh, $s, strlen($s));
					fclose($fh);
					$fh = fopen($unifilename . '.cw.dat', "wb");
					fwrite($fh, $cw, strlen($cw));
					fclose($fh);
					if(file_exists($unifilename)){
						unlink($unifilename . '.cw127.php');
					}
				}
				unset($ttf);				
			} else {
				$cw = @file_get_contents($unifilename . '.cw.dat');				
			}
			$i = count($this->fonts) + 1;
			if (!empty($this->AliasNbPages))
				$sbarr = range(0, 57);
			else
				$sbarr = range(0, 32);
			$this->fonts[$fontkey] = array('i' => $i, 'type' => $type, 'name' => $name, 'desc' => $desc, 'up' => $up, 'ut' => $ut, 'cw' => $cw, 'ttffile' => $ttffile, 'fontkey' => $fontkey, 'subset' => $sbarr, 'unifilename' => $unifilename);

			$this->FontFiles[$fontkey] = array('length1' => $originalsize, 'type' => "TTF", 'ttffile' => $ttffile);
			$this->FontFiles[$file] = array('type' => "TTF");
			unset($cw);
		} else {
			$info = $this->_loadfont($file);
			$info['i'] = count($this->fonts) + 1;
			if (!empty($info['diff'])) {
				// Search existing encodings
				$n = array_search($info['diff'], $this->diffs);
				if (!$n) {
					$n = count($this->diffs) + 1;
					$this->diffs[$n] = $info['diff'];
				}
				$info['diffn'] = $n;
			}
			if (!empty($info['file'])) {
				// Embedded font
				if ($info['type'] == 'TrueType')
					$this->FontFiles[$info['file']] = array('length1' => $info['originalsize']);
				else
					$this->FontFiles[$info['file']] = array('length1' => $info['size1'], 'length2' => $info['size2']);
			}
			$this->fonts[$fontkey] = $info;
		}
	}

	/**
	 * 
	 * @param type $family
	 * @param string $style B|I
	 * @param type $size
	 * @return type
	 */
	function SetFont($family, $style = '', $size = 0) {
		// Select a font; size given in points
		if ($family == '')
			$family = $this->FontFamily;
		else
			$family = strtolower($family);
		$style = strtoupper($style);
		if (strpos($style, 'U') !== false) {
			$this->underline = true;
			$style = str_replace('U', '', $style);
		} else
			$this->underline = false;
		if ($style == 'IB')
			$style = 'BI';
		if ($size == 0)
			$size = $this->FontSizePt;
		// Test if font is already selected
		if ($this->FontFamily == $family && $this->FontStyle == $style && $this->FontSizePt == $size)
			return;
		// Test if font is already loaded
		$fontkey = $family . $style;
		if (!isset($this->fonts[$fontkey])) {
			// Test if one of the core fonts
			if ($family == 'arial')
				$family = 'helvetica';
			if (in_array($family, $this->CoreFonts)) {
				if ($family == 'symbol' || $family == 'zapfdingbats')
					$style = '';
				$fontkey = $family . $style;
				if (!isset($this->fonts[$fontkey]))
					$this->AddFont($family, $style);
			} else
				$this->Error('Undefined font: ' . $family . ' ' . $style);
		}
		// Select it
		$this->FontFamily = $family;
		$this->FontStyle = $style;
		$this->FontSizePt = $size;
		$this->FontSize = $size / $this->k;
		$this->CurrentFont = &$this->fonts[$fontkey];
		if ($this->fonts[$fontkey]['type'] == 'TTF') {
			$this->unifontSubset = true;
		} else {
			$this->unifontSubset = false;
		}
		if ($this->page > 0)
			$this->_out(sprintf('BT /F%d %.2F Tf ET', $this->CurrentFont['i'], $this->FontSizePt));
	}

	function SetFontSize($size) {
		// Set font size in points
		if ($this->FontSizePt == $size)
			return;
		$this->FontSizePt = $size;
		$this->FontSize = $size / $this->k;
		if ($this->page > 0)
			$this->_out(sprintf('BT /F%d %.2F Tf ET', $this->CurrentFont['i'], $this->FontSizePt));
	}

	function AddLink() {
		// Create a new internal link
		$n = count($this->links) + 1;
		$this->links[$n] = array(0, 0);
		return $n;
	}

	function SetLink($link, $y = 0, $page = -1) {
		// Set destination of internal link
		if ($y == -1)
			$y = $this->y;
		if ($page == -1)
			$page = $this->page;
		$this->links[$link] = array($page, $y);
	}

	function Link($x, $y, $w, $h, $link) {
		// Put a link on the page
		$this->PageLinks[$this->page][] = array($x * $this->k, $this->hPt - $y * $this->k, $w * $this->k, $h * $this->k, $link);
	}

	function Text($x, $y, $txt) {
		// Output a string
		if ($this->unifontSubset) {
			$txt2 = '(' . $this->_escape($this->UTF8ToUTF16BE($txt, false)) . ')';
			foreach ($this->UTF8StringToArray($txt) as $uni)
				$this->CurrentFont['subset'][$uni] = $uni;
		} else
			$txt2 = '(' . $this->_escape($txt) . ')';
		$s = sprintf('BT %.2F %.2F Td %s Tj ET', $x * $this->k, ($this->h - $y) * $this->k, $txt2);
		if ($this->underline && $txt != '')
			$s .= ' ' . $this->_dounderline($x, $y, $txt);
		if ($this->ColorFlag)
			$s = 'q ' . $this->TextColor . ' ' . $s . ' Q';
		$this->_out($s);
	}

	function AcceptPageBreak() {
		// Accept automatic page break or not
		return $this->AutoPageBreak;
	}

	/**
	 * 
	 * @param int $w
	 * @param int $h
	 * @param string $txt
	 * @param int $border
	 * @param int $ln
	 * @param string $align
	 * @param int $fill
	 * @param string $link
	 */
	function Cell($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false, $link = '') {
		// Output a cell
		$k = $this->k;
		if ($this->y + $h > $this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AcceptPageBreak()) {
			// Automatic page break
			$x = $this->x;
			$ws = $this->ws;
			if ($ws > 0) {
				$this->ws = 0;
				$this->_out('0 Tw');
			}
			$this->AddPage($this->CurOrientation, $this->CurPageSize);
			$this->x = $x;
			if ($ws > 0) {
				$this->ws = $ws;
				$this->_out(sprintf('%.3F Tw', $ws * $k));
			}
		}
		if ($w == 0)
			$w = $this->w - $this->rMargin - $this->x;
		$s = '';
		if ($fill || $border == 1) {
			if ($fill)
				$op = ($border == 1) ? 'B' : 'f';
			else
				$op = 'S';
			$s = sprintf('%.2F %.2F %.2F %.2F re %s ', $this->x * $k, ($this->h - $this->y) * $k, $w * $k, -$h * $k, $op);
		}
		if (is_string($border)) {
			$x = $this->x;
			$y = $this->y;
			if (strpos($border, 'L') !== false)
				$s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x * $k, ($this->h - $y) * $k, $x * $k, ($this->h - ($y + $h)) * $k);
			if (strpos($border, 'T') !== false)
				$s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x * $k, ($this->h - $y) * $k, ($x + $w) * $k, ($this->h - $y) * $k);
			if (strpos($border, 'R') !== false)
				$s .= sprintf('%.2F %.2F m %.2F %.2F l S ', ($x + $w) * $k, ($this->h - $y) * $k, ($x + $w) * $k, ($this->h - ($y + $h)) * $k);
			if (strpos($border, 'B') !== false)
				$s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x * $k, ($this->h - ($y + $h)) * $k, ($x + $w) * $k, ($this->h - ($y + $h)) * $k);
		}
		if ($txt !== '') {
			if ($align == 'R')
				$dx = $w - $this->cMargin - $this->GetStringWidth($txt);
			elseif ($align == 'C')
				$dx = ($w - $this->GetStringWidth($txt)) / 2;
			else
				$dx = $this->cMargin;
			if ($this->ColorFlag)
				$s .= 'q ' . $this->TextColor . ' ';

			// If multibyte, Tw has no effect - do word spacing using an adjustment before each space
			if ($this->ws && $this->unifontSubset) {
				foreach ($this->UTF8StringToArray($txt) as $uni)
					$this->CurrentFont['subset'][$uni] = $uni;
				$space = $this->_escape($this->UTF8ToUTF16BE(' ', false));
				$s .= sprintf('BT 0 Tw %.2F %.2F Td [', ($this->x + $dx) * $k, ($this->h - ($this->y + .5 * $h + .3 * $this->FontSize)) * $k);
				$t = explode(' ', $txt);
				$numt = count($t);
				for ($i = 0; $i < $numt; $i++) {
					$tx = $t[$i];
					$tx = '(' . $this->_escape($this->UTF8ToUTF16BE($tx, false)) . ')';
					$s .= sprintf('%s ', $tx);
					if (($i + 1) < $numt) {
						$adj = -($this->ws * $this->k) * 1000 / $this->FontSizePt;
						$s .= sprintf('%d(%s) ', $adj, $space);
					}
				}
				$s .= '] TJ';
				$s .= ' ET';
			} else {
				if ($this->unifontSubset) {
					$txt2 = '(' . $this->_escape($this->UTF8ToUTF16BE($txt, false)) . ')';
					foreach ($this->UTF8StringToArray($txt) as $uni)
						$this->CurrentFont['subset'][$uni] = $uni;
				} else
					$txt2 = '(' . str_replace(')', '\\)', str_replace('(', '\\(', str_replace('\\', '\\\\', $txt))) . ')';
				$s .= sprintf('BT %.2F %.2F Td %s Tj ET', ($this->x + $dx) * $k, ($this->h - ($this->y + .5 * $h + .3 * $this->FontSize)) * $k, $txt2);
			}
			if ($this->underline)
				$s .= ' ' . $this->_dounderline($this->x + $dx, $this->y + .5 * $h + .3 * $this->FontSize, $txt);
			if ($this->ColorFlag)
				$s .= ' Q';
			if ($link)
				$this->Link($this->x + $dx, $this->y + .5 * $h - .5 * $this->FontSize, $this->GetStringWidth($txt), $this->FontSize, $link);
		}
		if ($s)
			$this->_out($s);
		$this->lasth = $h;
		if ($ln > 0) {
			// Go to next line
			$this->y += $h;
			if ($ln == 1)
				$this->x = $this->lMargin;
		} else
			$this->x += $w;
	}

	/**
	 * 
	 * @param int $w
	 * @param int $h
	 * @param string $txt
	 * @param int $border
	 * @param string $align
	 * @param int $fill
	 */
	function MultiCell($w, $h, $txt, $border = 0, $align = 'J', $fill = false) {
		// Output text with automatic or explicit line breaks
		$cw = &$this->CurrentFont['cw'];
		if ($w == 0)
			$w = $this->w - $this->rMargin - $this->x;
		$wmax = ($w - 2 * $this->cMargin);
		$s = str_replace("\r", '', $txt??'');
		if ($this->unifontSubset) {
			$nb = mb_strlen($s, 'utf-8');
			while ($nb > 0 && mb_substr($s, $nb - 1, 1, 'utf-8') == "\n")
				$nb--;
		} else {
			$nb = strlen($s);
			if ($nb > 0 && $s[$nb - 1] == "\n")
				$nb--;
		}
		$b = 0;
		if ($border) {
			if ($border == 1) {
				$border = 'LTRB';
				$b = 'LRT';
				$b2 = 'LR';
			} else {
				$b2 = '';
				if (strpos($border, 'L') !== false)
					$b2 .= 'L';
				if (strpos($border, 'R') !== false)
					$b2 .= 'R';
				$b = (strpos($border, 'T') !== false) ? $b2 . 'T' : $b2;
			}
		}
		$sep = -1;
		$i = 0;
		$j = 0;
		$l = 0;
		$ns = 0;
		$nl = 1;
		while ($i < $nb) {
			// Get next character
			if ($this->unifontSubset) {
				$c = mb_substr($s, $i, 1, 'UTF-8');
			} else {
				$c = $s[$i];
			}
			if ($c == "\n") {
				// Explicit line break
				if ($this->ws > 0) {
					$this->ws = 0;
					$this->_out('0 Tw');
				}
				if ($this->unifontSubset) {
					$this->Cell($w, $h, mb_substr($s, $j, $i - $j, 'UTF-8'), $b, 2, $align, $fill);
				} else {
					$this->Cell($w, $h, substr($s, $j, $i - $j), $b, 2, $align, $fill);
				}
				$i++;
				$sep = -1;
				$j = $i;
				$l = 0;
				$ns = 0;
				$nl++;
				if ($border && $nl == 2)
					$b = $b2;
				continue;
			}
			if ($c == ' ') {
				$sep = $i;
				$ls = $l;
				$ns++;
			}

			if ($this->unifontSubset) {
				$l += $this->GetStringWidth($c);
			} else {
				$l += $cw[$c] * $this->FontSize / 1000;
			}

			if ($l > $wmax) {
				// Automatic line break
				if ($sep == -1) {
					if ($i == $j)
						$i++;
					if ($this->ws > 0) {
						$this->ws = 0;
						$this->_out('0 Tw');
					}
					if ($this->unifontSubset) {
						$this->Cell($w, $h, mb_substr($s, $j, $i - $j, 'UTF-8'), $b, 2, $align, $fill);
					} else {
						$this->Cell($w, $h, substr($s, $j, $i - $j), $b, 2, $align, $fill);
					}
				} else {
					if ($align == 'J') {
						$this->ws = ($ns > 1) ? ($wmax - $ls) / ($ns - 1) : 0;
						$this->_out(sprintf('%.3F Tw', $this->ws * $this->k));
					}
					if ($this->unifontSubset) {
						$this->Cell($w, $h, mb_substr($s, $j, $sep - $j, 'UTF-8'), $b, 2, $align, $fill);
					} else {
						$this->Cell($w, $h, substr($s, $j, $sep - $j), $b, 2, $align, $fill);
					}
					$i = $sep + 1;
				}
				$sep = -1;
				$j = $i;
				$l = 0;
				$ns = 0;
				$nl++;
				if ($border && $nl == 2)
					$b = $b2;
			} else
				$i++;
		}
		// Last chunk
		if ($this->ws > 0) {
			$this->ws = 0;
			$this->_out('0 Tw');
		}
		if ($border && strpos($border, 'B') !== false)
			$b .= 'B';
		if ($this->unifontSubset) {
			$this->Cell($w, $h, mb_substr($s, $j, $i - $j, 'UTF-8'), $b, 2, $align, $fill);
		} else {
			$this->Cell($w, $h, substr($s, $j, $i - $j), $b, 2, $align, $fill);
		}
		$this->x = $this->lMargin;
	}

	function Write($h, $txt, $link = '', $align = '') {
		// Output text in flowing mode
		$cw = &$this->CurrentFont['cw'];
		$w = $this->w - $this->rMargin - $this->x;

		$wmax = ($w - 2 * $this->cMargin);
		$s = str_replace("\r", '', $txt);
		if ($this->unifontSubset) {
			$nb = mb_strlen($s, 'UTF-8');
			if ($nb == 1 && $s == " ") {
				$this->x += $this->GetStringWidth($s);
				return;
			}
		} else {
			$nb = strlen($s);
		}
		$sep = -1;
		$i = 0;
		$j = 0;
		$l = 0;
		$nl = 1;
		while ($i < $nb) {
			// Get next character
			if ($this->unifontSubset) {
				$c = mb_substr($s, $i, 1, 'UTF-8');
			} else {
				$c = $s[$i];
			}
			if ($c == "\n") {
				// Explicit line break
				if ($this->unifontSubset) {
					$this->Cell($w, $h, mb_substr($s, $j, $i - $j, 'UTF-8'), 0, 2, $align, 0, $link);
				} else {
					$this->Cell($w, $h, substr($s, $j, $i - $j), 0, 2, $align, 0, $link);
				}
				$i++;
				$sep = -1;
				$j = $i;
				$l = 0;
				if ($nl == 1) {
					$this->x = $this->lMargin;
					$w = $this->w - $this->rMargin - $this->x;
					$wmax = ($w - 2 * $this->cMargin);
				}
				$nl++;
				continue;
			}
			if ($c == ' ')
				$sep = $i;

			if ($this->unifontSubset) {
				$l += $this->GetStringWidth($c);
			} else {
				$l += $cw[$c] * $this->FontSize / 1000;
			}

			if ($l > $wmax) {
				// Automatic line break
				if ($sep == -1) {
					if ($this->x > $this->lMargin) {
						// Move to next line
						$this->x = $this->lMargin;
						$this->y += $h;
						$w = $this->w - $this->rMargin - $this->x;
						$wmax = ($w - 2 * $this->cMargin);
						$i++;
						$nl++;
						continue;
					}
					if ($i == $j)
						$i++;
					if ($this->unifontSubset) {
						$this->Cell($w, $h, mb_substr($s, $j, $i - $j, 'UTF-8'), 0, 2, $align, 0, $link);
					} else {
						$this->Cell($w, $h, substr($s, $j, $i - $j), 0, 2, $align, 0, $link);
					}
				} else {
					if ($this->unifontSubset) {
						$this->Cell($w, $h, mb_substr($s, $j, $sep - $j, 'UTF-8'), 0, 2, $align, 0, $link);
					} else {
						$this->Cell($w, $h, substr($s, $j, $sep - $j), 0, 2, $align, 0, $link);
					}
					$i = $sep + 1;
				}
				$sep = -1;
				$j = $i;
				$l = 0;
				if ($nl == 1) {
					$this->x = $this->lMargin;
					$w = $this->w - $this->rMargin - $this->x;
					$wmax = ($w - 2 * $this->cMargin);
				}
				$nl++;
			} else
				$i++;
		}
		// Last chunk
		if ($i != $j) {
			if ($this->unifontSubset) {
				$this->Cell($l, $h, mb_substr($s, $j, $i - $j, 'UTF-8'), 0, 0, $align, 0, $link);
			} else {
				$this->Cell($l, $h, substr($s, $j), 0, 0, $align, 0, $link);
			}
		}
	}

	function Ln($h = null) {
		// Line feed; default value is last cell height
		$this->x = $this->lMargin;
		if ($h === null)
			$this->y += $this->lasth;
		else
			$this->y += $h;
	}

	/**
     * 
     * @param string $file
     * @param int $x
     * @param int $y
     * @param int $w
     * @param int $h
     * @param string $type jpg, png
     * @param string $link
     */
	function Image($file, $x = null, $y = null, $w = 0, $h = 0, $type = '', $link = '') {
		// Put an image on the page
		if (!isset($this->images[$file])) {
			// First use of this image, get info
			if ($type == '') {
				$pos = strrpos($file, '.');
				if (!$pos)
					$this->Error('Image file has no extension and no type was specified: ' . $file);
				$type = substr($file, $pos + 1);
			}
			$type = strtolower($type);
			if ($type == 'jpeg')
				$type = 'jpg';
			$mtd = '_parse' . $type;
			if (!method_exists($this, $mtd))
				$this->Error('Unsupported image type: ' . $type);
			$info = $this->$mtd($file);
			$info['i'] = count($this->images) + 1;
			$this->images[$file] = $info;
		} else
			$info = $this->images[$file];

		// Automatic width and height calculation if needed
		if ($w == 0 && $h == 0) {
			// Put image at 96 dpi
			$w = -96;
			$h = -96;
		}
		if ($w < 0)
			$w = -$info['w'] * 72 / $w / $this->k;
		if ($h < 0)
			$h = -$info['h'] * 72 / $h / $this->k;
		if ($w == 0)
			$w = $h * $info['w'] / $info['h'];
		if ($h == 0)
			$h = $w * $info['h'] / $info['w'];

		// Flowing mode
		if ($y === null) {
			if ($this->y + $h > $this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AcceptPageBreak()) {
				// Automatic page break
				$x2 = $this->x;
				$this->AddPage($this->CurOrientation, $this->CurPageSize);
				$this->x = $x2;
			}
			$y = $this->y;
			$this->y += $h;
		}

		if ($x === null)
			$x = $this->x;
		$this->_out(sprintf('q %.2F 0 0 %.2F %.2F %.2F cm /I%d Do Q', $w * $this->k, $h * $this->k, $x * $this->k, ($this->h - ($y + $h)) * $this->k, $info['i']));
		if ($link)
			$this->Link($x, $y, $w, $h, $link);
	}

	function GetX() {
		// Get x position
		return $this->x;
	}

	function SetX($x) {
		// Set x position
		if ($x >= 0)
			$this->x = $x;
		else
			$this->x = $this->w + $x;
	}

	function GetY() {
		// Get y position
		return $this->y;
	}

	function SetY($y) {
		// Set y position and reset x
		$this->x = $this->lMargin;
		if ($y >= 0)
			$this->y = $y;
		else
			$this->y = $this->h + $y;
	}

	function SetXY($x, $y) {
		// Set x and y positions
		$this->SetY($y);
		$this->SetX($x);
	}

	function Output($name = '', $dest = '') {


		// Output PDF to some destination
		if ($this->state < 3)
			$this->Close();

		$dest = strtoupper($dest);
		if ($dest == '') {
			if ($name == '') {
				$name = 'doc.pdf';
				$dest = 'I';
			} else
				$dest = 'F';
		}
		switch ($dest) {
			case 'I':
				// Send to standard output
				$this->_checkoutput();
				if (PHP_SAPI != 'cli') {
					// We send to a browser
					header('Content-Type: application/pdf');
					header('Content-Disposition: inline; filename="' . $name . '"');
					header('Cache-Control: private, max-age=0, must-revalidate');
					header('Pragma: public');
				}
				echo $this->buffer;
				break;
			case 'D':
				// Download file
				$this->_checkoutput();
				header('Content-Type: application/x-download');
				header('Content-Disposition: attachment; filename="' . $name . '"');
				header('Cache-Control: private, max-age=0, must-revalidate');
				header('Pragma: public');
				echo $this->buffer;
				break;
			case 'F':
				// Save to local file
				$f = fopen($name, 'wb');
				if (!$f)
					$this->Error('Unable to create output file: ' . $name);
				fwrite($f, $this->buffer, strlen($this->buffer));
				fclose($f);
				break;
			case 'S':
				// Return as a string
				return $this->buffer;
			default:
				$this->Error('Incorrect output destination: ' . $dest);
		}
		return '';
	}

	/*	 * *****************************************************************************
	 *                                                                              *
	 *                              Protected methods                               *
	 *                                                                              *
	 * ***************************************************************************** */

	function _dochecks() {
		// Check availability of %F
		if (sprintf('%.1F', 1.0) != '1.0')
			$this->Error('This version of PHP is not supported');
		// Check availability of mbstring
		if (!function_exists('mb_strlen'))
			$this->Error('mbstring extension is not available');
		// Check mbstring overloading
		if (ini_get('mbstring.func_overload') & 2)
			$this->Error('mbstring overloading must be disabled');
		// Ensure runtime magic quotes are disabled
		ini_set('magic_quotes_runtime', 0);
//		if (get_magic_quotes_runtime())
//			@set_magic_quotes_runtime(0);
	}

	function _getfontpath() {
		return $this->fontpath;
	}

	function _checkoutput() {
		if (PHP_SAPI != 'cli') {
			if (headers_sent($file, $line))
				$this->Error("Some data has already been output, can't send PDF file (output started at $file:$line)");
		}
		if (ob_get_length()) {
			// The output buffer is not empty
			if (preg_match('/^(\xEF\xBB\xBF)?\s*$/', ob_get_contents())) {
				// It contains only a UTF-8 BOM and/or whitespace, let's clean it
				ob_clean();
			} else
				$this->Error("Some data has already been output, can't send PDF file");
		}
	}

	function _getpagesize($size) {
		if (is_string($size)) {
			$size = strtolower($size);
			if (!isset($this->StdPageSizes[$size]))
				$this->Error('Unknown page size: ' . $size);
			$a = $this->StdPageSizes[$size];
			return array($a[0] / $this->k, $a[1] / $this->k);
		} else {
			if ($size[0] > $size[1])
				return array($size[1], $size[0]);
			else
				return $size;
		}
	}

	function _beginpage($orientation, $size) {
		$this->page++;
		$this->pages[$this->page] = '';
		$this->state = 2;
		$this->x = $this->lMargin;
		$this->y = $this->tMargin;
		$this->FontFamily = '';
		// Check page size and orientation
		if ($orientation == '')
			$orientation = $this->DefOrientation;
		else
			$orientation = strtoupper($orientation[0]);
		if ($size == '')
			$size = $this->DefPageSize;
		else
			$size = $this->_getpagesize($size);
		if ($orientation != $this->CurOrientation || $size[0] != $this->CurPageSize[0] || $size[1] != $this->CurPageSize[1]) {
			// New size or orientation
			if ($orientation == 'P') {
				$this->w = $size[0];
				$this->h = $size[1];
			} else {
				$this->w = $size[1];
				$this->h = $size[0];
			}
			$this->wPt = $this->w * $this->k;
			$this->hPt = $this->h * $this->k;
			$this->PageBreakTrigger = $this->h - $this->bMargin;
			$this->CurOrientation = $orientation;
			$this->CurPageSize = $size;
		}
		if ($orientation != $this->DefOrientation || $size[0] != $this->DefPageSize[0] || $size[1] != $this->DefPageSize[1])
			$this->PageSizes[$this->page] = array($this->wPt, $this->hPt);
	}

	function _endpage() {
		$this->state = 1;
	}

	function _loadfont($font) {
		// Load a font definition file from the font directory
		include($this->fontpath . $font);
		$a = get_defined_vars();
		if (!isset($a['name']))
			$this->Error('Could not include font definition file');
		return $a;
	}

	function _escape($s) {
		// Escape special characters in strings
		$s = str_replace('\\', '\\\\', $s);
		$s = str_replace('(', '\\(', $s);
		$s = str_replace(')', '\\)', $s);
		$s = str_replace("\r", '\\r', $s);
		return $s;
	}

	function _textstring($s) {
		// Format a text string
		return '(' . $this->_escape($s) . ')';
	}

	function _UTF8toUTF16($s) {
		// Convert UTF-8 to UTF-16BE with BOM
		$res = "\xFE\xFF";
		$nb = strlen($s);
		$i = 0;
		while ($i < $nb) {
			$c1 = ord($s[$i++]);
			if ($c1 >= 224) {
				// 3-byte character
				$c2 = ord($s[$i++]);
				$c3 = ord($s[$i++]);
				$res .= chr((($c1 & 0x0F) << 4) + (($c2 & 0x3C) >> 2));
				$res .= chr((($c2 & 0x03) << 6) + ($c3 & 0x3F));
			} elseif ($c1 >= 192) {
				// 2-byte character
				$c2 = ord($s[$i++]);
				$res .= chr(($c1 & 0x1C) >> 2);
				$res .= chr((($c1 & 0x03) << 6) + ($c2 & 0x3F));
			} else {
				// Single-byte character
				$res .= "\0" . chr($c1);
			}
		}
		return $res;
	}

	function _dounderline($x, $y, $txt) {
		// Underline text
		$up = $this->CurrentFont['up'];
		$ut = $this->CurrentFont['ut'];
		$w = $this->GetStringWidth($txt) + $this->ws * substr_count($txt, ' ');
		return sprintf('%.2F %.2F %.2F %.2F re f', $x * $this->k, ($this->h - ($y - $up / 1000 * $this->FontSize)) * $this->k, $w * $this->k, -$ut / 1000 * $this->FontSizePt);
	}

	function _parsejpg($file) {
		// Extract info from a JPEG file
		$a = getimagesize($file);
		if (!$a)
			$this->Error('Missing or incorrect image file: ' . $file);
		if ($a[2] != 2)
			$this->Error('Not a JPEG file: ' . $file);
		if (!isset($a['channels']) || $a['channels'] == 3)
			$colspace = 'DeviceRGB';
		elseif ($a['channels'] == 4)
			$colspace = 'DeviceCMYK';
		else
			$colspace = 'DeviceGray';
		$bpc = isset($a['bits']) ? $a['bits'] : 8;
		$data = file_get_contents($file);
		return array('w' => $a[0], 'h' => $a[1], 'cs' => $colspace, 'bpc' => $bpc, 'f' => 'DCTDecode', 'data' => $data);
	}

	function _parsepng($file) {
		// Extract info from a PNG file
		$f = fopen($file, 'rb');
		if (!$f)
			$this->Error('Can\'t open image file: ' . $file);
		$info = $this->_parsepngstream($f, $file);
		fclose($f);
		return $info;
	}

	function _parsepngstream($f, $file) {
		// Check signature
		if ($this->_readstream($f, 8) != chr(137) . 'PNG' . chr(13) . chr(10) . chr(26) . chr(10))
			$this->Error('Not a PNG file: ' . $file);

		// Read header chunk
		$this->_readstream($f, 4);
		if ($this->_readstream($f, 4) != 'IHDR')
			$this->Error('Incorrect PNG file: ' . $file);
		$w = $this->_readint($f);
		$h = $this->_readint($f);
		$bpc = ord($this->_readstream($f, 1));
		if ($bpc > 8)
			$this->Error('16-bit depth not supported: ' . $file);
		$ct = ord($this->_readstream($f, 1));
		if ($ct == 0 || $ct == 4)
			$colspace = 'DeviceGray';
		elseif ($ct == 2 || $ct == 6)
			$colspace = 'DeviceRGB';
		elseif ($ct == 3)
			$colspace = 'Indexed';
		else
			$this->Error('Unknown color type: ' . $file);
		if (ord($this->_readstream($f, 1)) != 0)
			$this->Error('Unknown compression method: ' . $file);
		if (ord($this->_readstream($f, 1)) != 0)
			$this->Error('Unknown filter method: ' . $file);
		if (ord($this->_readstream($f, 1)) != 0)
			$this->Error('Interlacing not supported: ' . $file);
		$this->_readstream($f, 4);
		$dp = '/Predictor 15 /Colors ' . ($colspace == 'DeviceRGB' ? 3 : 1) . ' /BitsPerComponent ' . $bpc . ' /Columns ' . $w;

		// Scan chunks looking for palette, transparency and image data
		$pal = '';
		$trns = '';
		$data = '';
		do {
			$n = $this->_readint($f);
			$type = $this->_readstream($f, 4);
			if ($type == 'PLTE') {
				// Read palette
				$pal = $this->_readstream($f, $n);
				$this->_readstream($f, 4);
			} elseif ($type == 'tRNS') {
				// Read transparency info
				$t = $this->_readstream($f, $n);
				if ($ct == 0)
					$trns = array(ord(substr($t, 1, 1)));
				elseif ($ct == 2)
					$trns = array(ord(substr($t, 1, 1)), ord(substr($t, 3, 1)), ord(substr($t, 5, 1)));
				else {
					$pos = strpos($t, chr(0));
					if ($pos !== false)
						$trns = array($pos);
				}
				$this->_readstream($f, 4);
			} elseif ($type == 'IDAT') {
				// Read image data block
				$data .= $this->_readstream($f, $n);
				$this->_readstream($f, 4);
			} elseif ($type == 'IEND')
				break;
			else
				$this->_readstream($f, $n + 4);
		}
		while ($n);

		if ($colspace == 'Indexed' && empty($pal))
			$this->Error('Missing palette in ' . $file);
		$info = array('w' => $w, 'h' => $h, 'cs' => $colspace, 'bpc' => $bpc, 'f' => 'FlateDecode', 'dp' => $dp, 'pal' => $pal, 'trns' => $trns);
		if ($ct >= 4) {
			// Extract alpha channel
			if (!function_exists('gzuncompress'))
				$this->Error('Zlib not available, can\'t handle alpha channel: ' . $file);
			$data = gzuncompress($data);
			$color = '';
			$alpha = '';
			if ($ct == 4) {
				// Gray image
				$len = 2 * $w;
				for ($i = 0; $i < $h; $i++) {
					$pos = (1 + $len) * $i;
					$color .= $data[$pos];
					$alpha .= $data[$pos];
					$line = substr($data, $pos + 1, $len);
					$color .= preg_replace('/(.)./s', '$1', $line);
					$alpha .= preg_replace('/.(.)/s', '$1', $line);
				}
			} else {
				// RGB image
				$len = 4 * $w;
				for ($i = 0; $i < $h; $i++) {
					$pos = (1 + $len) * $i;
					$color .= $data[$pos];
					$alpha .= $data[$pos];
					$line = substr($data, $pos + 1, $len);
					$color .= preg_replace('/(.{3})./s', '$1', $line);
					$alpha .= preg_replace('/.{3}(.)/s', '$1', $line);
				}
			}
			unset($data);
			$data = gzcompress($color);
			$info['smask'] = gzcompress($alpha);
			if ($this->PDFVersion < '1.4')
				$this->PDFVersion = '1.4';
		}
		$info['data'] = $data;
		return $info;
	}

	function _readstream($f, $n) {
		// Read n bytes from stream
		$res = '';
		while ($n > 0 && !feof($f)) {
			$s = fread($f, $n);
			if ($s === false)
				$this->Error('Error while reading stream');
			$n -= strlen($s);
			$res .= $s;
		}
		if ($n > 0)
			$this->Error('Unexpected end of stream');
		return $res;
	}

	function _readint($f) {
		// Read a 4-byte integer from stream
		$a = unpack('Ni', $this->_readstream($f, 4));
		return $a['i'];
	}

	function _parsegif($file) {
		// Extract info from a GIF file (via PNG conversion)
		if (!function_exists('imagepng'))
			$this->Error('GD extension is required for GIF support');
		if (!function_exists('imagecreatefromgif'))
			$this->Error('GD has no GIF read support');
		$im = imagecreatefromgif($file);
		if (!$im)
			$this->Error('Missing or incorrect image file: ' . $file);
		imageinterlace($im, 0);
		$f = @fopen('php://temp', 'rb+');
		if ($f) {
			// Perform conversion in memory
			ob_start();
			imagepng($im);
			$data = ob_get_clean();
			imagedestroy($im);
			fwrite($f, $data);
			rewind($f);
			$info = $this->_parsepngstream($f, $file);
			fclose($f);
		} else {
			// Use temporary file
			$tmp = tempnam('.', 'gif');
			if (!$tmp)
				$this->Error('Unable to create a temporary file');
			if (!imagepng($im, $tmp))
				$this->Error('Error while saving to temporary file');
			imagedestroy($im);
			$info = $this->_parsepng($tmp);
			unlink($tmp);
		}
		return $info;
	}

	function _newobj() {
		// Begin a new object
		$this->n++;
		$this->offsets[$this->n] = strlen($this->buffer);
		$this->_out($this->n . ' 0 obj');
	}

	function _putstream($s) {
		$this->_out('stream');
		$this->_out($s);
		$this->_out('endstream');
	}

	function _out($s) {
		// Add a line to the document
		if ($this->state == 2)
			$this->pages[$this->page] .= $s . "\n";
		else
			$this->buffer .= $s . "\n";
	}

	function _putpages() {
		$nb = $this->page;
		if (!empty($this->AliasNbPages)) {
			// Replace number of pages in fonts using subsets
			$alias = $this->UTF8ToUTF16BE($this->AliasNbPages, false);
			$r = $this->UTF8ToUTF16BE("$nb", false);
			for ($n = 1; $n <= $nb; $n++)
				$this->pages[$n] = str_replace($alias, $r, $this->pages[$n]);
			// Now repeat for no pages in non-subset fonts
			for ($n = 1; $n <= $nb; $n++)
				$this->pages[$n] = str_replace($this->AliasNbPages, $nb, $this->pages[$n]);
		}
		if ($this->DefOrientation == 'P') {
			$wPt = $this->DefPageSize[0] * $this->k;
			$hPt = $this->DefPageSize[1] * $this->k;
		} else {
			$wPt = $this->DefPageSize[1] * $this->k;
			$hPt = $this->DefPageSize[0] * $this->k;
		}
		$filter = ($this->compress) ? '/Filter /FlateDecode ' : '';
		for ($n = 1; $n <= $nb; $n++) {
			// Page
			$this->_newobj();
			$this->_out('<</Type /Page');
			$this->_out('/Parent 1 0 R');
			if (isset($this->PageSizes[$n]))
				$this->_out(sprintf('/MediaBox [0 0 %.2F %.2F]', $this->PageSizes[$n][0], $this->PageSizes[$n][1]));
			$this->_out('/Resources 2 0 R');
			if (isset($this->PageLinks[$n])) {
				// Links
				$annots = '/Annots [';
				foreach ($this->PageLinks[$n] as $pl) {
					$rect = sprintf('%.2F %.2F %.2F %.2F', $pl[0], $pl[1], $pl[0] + $pl[2], $pl[1] - $pl[3]);
					$annots .= '<</Type /Annot /Subtype /Link /Rect [' . $rect . '] /Border [0 0 0] ';
					if (is_string($pl[4]))
						$annots .= '/A <</S /URI /URI ' . $this->_textstring($pl[4]) . '>>>>';
					else {
						$l = $this->links[$pl[4]];
						$h = isset($this->PageSizes[$l[0]]) ? $this->PageSizes[$l[0]][1] : $hPt;
						$annots .= sprintf('/Dest [%d 0 R /XYZ 0 %.2F null]>>', 1 + 2 * $l[0], $h - $l[1] * $this->k);
					}
				}
				$this->_out($annots . ']');
			}
			if ($this->PDFVersion > '1.3')
				$this->_out('/Group <</Type /Group /S /Transparency /CS /DeviceRGB>>');
			$this->_out('/Contents ' . ($this->n + 1) . ' 0 R>>');
			$this->_out('endobj');
			// Page content
			$p = ($this->compress) ? gzcompress($this->pages[$n]) : $this->pages[$n];
			$this->_newobj();
			$this->_out('<<' . $filter . '/Length ' . strlen($p) . '>>');
			$this->_putstream($p);
			$this->_out('endobj');
		}
		// Pages root
		$this->offsets[1] = strlen($this->buffer);
		$this->_out('1 0 obj');
		$this->_out('<</Type /Pages');
		$kids = '/Kids [';
		for ($i = 0; $i < $nb; $i++)
			$kids .= (3 + 2 * $i) . ' 0 R ';
		$this->_out($kids . ']');
		$this->_out('/Count ' . $nb);
		$this->_out(sprintf('/MediaBox [0 0 %.2F %.2F]', $wPt, $hPt));
		$this->_out('>>');
		$this->_out('endobj');
	}

	function _putfonts() {
		$nf = $this->n;
		foreach ($this->diffs as $diff) {
			// Encodings
			$this->_newobj();
			$this->_out('<</Type /Encoding /BaseEncoding /WinAnsiEncoding /Differences [' . $diff . ']>>');
			$this->_out('endobj');
		}
		foreach ($this->FontFiles as $file => $info) {
			if (!isset($info['type']) || $info['type'] != 'TTF') {
				// Font file embedding
				$this->_newobj();
				$this->FontFiles[$file]['n'] = $this->n;
				$font = '';
				$f = fopen($this->_getfontpath() . $file, 'rb', 1);
				if (!$f)
					$this->Error('Font file not found');
				while (!feof($f))
					$font .= fread($f, 8192);
				fclose($f);
				$compressed = (substr($file, -2) == '.z');
				if (!$compressed && isset($info['length2'])) {
					$header = (ord($font[0]) == 128);
					if ($header) {
						// Strip first binary header
						$font = substr($font, 6);
					}
					if ($header && ord($font[$info['length1']]) == 128) {
						// Strip second binary header
						$font = substr($font, 0, $info['length1']) . substr($font, $info['length1'] + 6);
					}
				}
				$this->_out('<</Length ' . strlen($font));
				if ($compressed)
					$this->_out('/Filter /FlateDecode');
				$this->_out('/Length1 ' . $info['length1']);
				if (isset($info['length2']))
					$this->_out('/Length2 ' . $info['length2'] . ' /Length3 0');
				$this->_out('>>');
				$this->_putstream($font);
				$this->_out('endobj');
			}
		}
		foreach ($this->fonts as $k => $font) {
			// Font objects
			//$this->fonts[$k]['n']=$this->n+1;
			$type = $font['type'];
			$name = $font['name'];
			if ($type == 'Core') {
				// Standard font
				$this->fonts[$k]['n'] = $this->n + 1;
				$this->_newobj();
				$this->_out('<</Type /Font');
				$this->_out('/BaseFont /' . $name);
				$this->_out('/Subtype /Type1');
				if ($name != 'Symbol' && $name != 'ZapfDingbats')
					$this->_out('/Encoding /WinAnsiEncoding');
				$this->_out('>>');
				$this->_out('endobj');
			} elseif ($type == 'Type1' || $type == 'TrueType') {
				// Additional Type1 or TrueType font
				$this->fonts[$k]['n'] = $this->n + 1;
				$this->_newobj();
				$this->_out('<</Type /Font');
				$this->_out('/BaseFont /' . $name);
				$this->_out('/Subtype /' . $type);
				$this->_out('/FirstChar 32 /LastChar 255');
				$this->_out('/Widths ' . ($this->n + 1) . ' 0 R');
				$this->_out('/FontDescriptor ' . ($this->n + 2) . ' 0 R');
				if ($font['enc']) {
					if (isset($font['diff']))
						$this->_out('/Encoding ' . ($nf + $font['diff']) . ' 0 R');
					else
						$this->_out('/Encoding /WinAnsiEncoding');
				}
				$this->_out('>>');
				$this->_out('endobj');
				// Widths
				$this->_newobj();
				$cw = &$font['cw'];
				$s = '[';
				for ($i = 32; $i <= 255; $i++)
					$s .= $cw[chr($i)] . ' ';
				$this->_out($s . ']');
				$this->_out('endobj');
				// Descriptor
				$this->_newobj();
				$s = '<</Type /FontDescriptor /FontName /' . $name;
				foreach ($font['desc'] as $k => $v)
					$s .= ' /' . $k . ' ' . $v;
				$file = $font['file'];
				if ($file)
					$s .= ' /FontFile' . ($type == 'Type1' ? '' : '2') . ' ' . $this->FontFiles[$file]['n'] . ' 0 R';
				$this->_out($s . '>>');
				$this->_out('endobj');
			}
			// TrueType embedded SUBSETS or FULL
			else if ($type == 'TTF') {
				$this->fonts[$k]['n'] = $this->n + 1;
				require_once($this->_getfontpath() . 'unifont/ttfonts.php');
				$ttf = new TTFontFile();
				$fontname = 'MPDFAA' . '+' . $font['name'];
				$subset = $font['subset'];
				unset($subset[0]);
				$ttfontstream = $ttf->makeSubset($font['ttffile'], $subset);
				$ttfontsize = strlen($ttfontstream);
				$fontstream = gzcompress($ttfontstream);
				$codeToGlyph = $ttf->codeToGlyph;
				unset($codeToGlyph[0]);

				// Type0 Font
				// A composite font - a font composed of other fonts, organized hierarchically
				$this->_newobj();
				$this->_out('<</Type /Font');
				$this->_out('/Subtype /Type0');
				$this->_out('/BaseFont /' . $fontname . '');
				$this->_out('/Encoding /Identity-H');
				$this->_out('/DescendantFonts [' . ($this->n + 1) . ' 0 R]');
				$this->_out('/ToUnicode ' . ($this->n + 2) . ' 0 R');
				$this->_out('>>');
				$this->_out('endobj');

				// CIDFontType2
				// A CIDFont whose glyph descriptions are based on TrueType font technology
				$this->_newobj();
				$this->_out('<</Type /Font');
				$this->_out('/Subtype /CIDFontType2');
				$this->_out('/BaseFont /' . $fontname . '');
				$this->_out('/CIDSystemInfo ' . ($this->n + 2) . ' 0 R');
				$this->_out('/FontDescriptor ' . ($this->n + 3) . ' 0 R');
				if (isset($font['desc']['MissingWidth'])) {
					$this->_out('/DW ' . $font['desc']['MissingWidth'] . '');
				}

				$this->_putTTfontwidths($font, $ttf->maxUni);

				$this->_out('/CIDToGIDMap ' . ($this->n + 4) . ' 0 R');
				$this->_out('>>');
				$this->_out('endobj');

				// ToUnicode
				$this->_newobj();
				$toUni = "/CIDInit /ProcSet findresource begin\n";
				$toUni .= "12 dict begin\n";
				$toUni .= "begincmap\n";
				$toUni .= "/CIDSystemInfo\n";
				$toUni .= "<</Registry (Adobe)\n";
				$toUni .= "/Ordering (UCS)\n";
				$toUni .= "/Supplement 0\n";
				$toUni .= ">> def\n";
				$toUni .= "/CMapName /Adobe-Identity-UCS def\n";
				$toUni .= "/CMapType 2 def\n";
				$toUni .= "1 begincodespacerange\n";
				$toUni .= "<0000> <FFFF>\n";
				$toUni .= "endcodespacerange\n";
				$toUni .= "1 beginbfrange\n";
				$toUni .= "<0000> <FFFF> <0000>\n";
				$toUni .= "endbfrange\n";
				$toUni .= "endcmap\n";
				$toUni .= "CMapName currentdict /CMap defineresource pop\n";
				$toUni .= "end\n";
				$toUni .= "end";
				$this->_out('<</Length ' . (strlen($toUni)) . '>>');
				$this->_putstream($toUni);
				$this->_out('endobj');

				// CIDSystemInfo dictionary
				$this->_newobj();
				$this->_out('<</Registry (Adobe)');
				$this->_out('/Ordering (UCS)');
				$this->_out('/Supplement 0');
				$this->_out('>>');
				$this->_out('endobj');

				// Font descriptor
				$this->_newobj();
				$this->_out('<</Type /FontDescriptor');
				$this->_out('/FontName /' . $fontname);
				foreach ($font['desc'] as $kd => $v) {
					if ($kd == 'Flags') {
						$v = $v | 4;
						$v = $v & ~32;
					} // SYMBOLIC font flag
					$this->_out(' /' . $kd . ' ' . $v);
				}
				$this->_out('/FontFile2 ' . ($this->n + 2) . ' 0 R');
				$this->_out('>>');
				$this->_out('endobj');

				// Embed CIDToGIDMap
				// A specification of the mapping from CIDs to glyph indices
				$cidtogidmap = '';
				$cidtogidmap = str_pad('', 256 * 256 * 2, "\x00");
				foreach ($codeToGlyph as $cc => $glyph) {
					$cidtogidmap[$cc * 2] = chr($glyph >> 8);
					$cidtogidmap[$cc * 2 + 1] = chr($glyph & 0xFF);
				}
				$cidtogidmap = gzcompress($cidtogidmap);
				$this->_newobj();
				$this->_out('<</Length ' . strlen($cidtogidmap) . '');
				$this->_out('/Filter /FlateDecode');
				$this->_out('>>');
				$this->_putstream($cidtogidmap);
				$this->_out('endobj');

				//Font file 
				$this->_newobj();
				$this->_out('<</Length ' . strlen($fontstream));
				$this->_out('/Filter /FlateDecode');
				$this->_out('/Length1 ' . $ttfontsize);
				$this->_out('>>');
				$this->_putstream($fontstream);
				$this->_out('endobj');
				unset($ttf);
			} else {
				// Allow for additional types
				$this->fonts[$k]['n'] = $this->n + 1;
				$mtd = '_put' . strtolower($type);
				if (!method_exists($this, $mtd))
					$this->Error('Unsupported font type: ' . $type);
				$this->$mtd($font);
			}
		}
	}

	function _putTTfontwidths(&$font, $maxUni) {
		if (file_exists($font['unifilename'] . '.cw127.php')) {
			include($font['unifilename'] . '.cw127.php');
			$startcid = 128;
		} else {
			$rangeid = 0;
			$range = array();
			$prevcid = -2;
			$prevwidth = -1;
			$interval = false;
			$startcid = 1;
		}
		$cwlen = $maxUni + 1;

		// for each character
		for ($cid = $startcid; $cid < $cwlen; $cid++) {
			if ($cid == 128 && (!file_exists($font['unifilename'] . '.cw127.php'))) {
				if (is_writable(dirname($this->_getfontpath() . 'unifont/x'))) {
					$fh = fopen($font['unifilename'] . '.cw127.php', "wb");
					$cw127 = '<?php' . "\n";
					$cw127 .= '$rangeid=' . $rangeid . ";\n";
					$cw127 .= '$prevcid=' . $prevcid . ";\n";
					$cw127 .= '$prevwidth=' . $prevwidth . ";\n";
					if ($interval) {
						$cw127 .= '$interval=true' . ";\n";
					} else {
						$cw127 .= '$interval=false' . ";\n";
					}
					$cw127 .= '$range=' . var_export($range, true) . ";\n";
					$cw127 .= "?>";
					fwrite($fh, $cw127, strlen($cw127));
					fclose($fh);
				}
			}
			if ((isset($font['cw'][$cid * 2]) && $font['cw'][$cid * 2] == "\00") && 
				(isset($font['cw'][$cid * 2 + 1]) && $font['cw'][$cid * 2 + 1] == "\00")
				) {
				continue;
			}
			
			// Verificar se os índices existem antes de usar ord()
			if (!isset($font['cw'][$cid * 2]) || !isset($font['cw'][$cid * 2 + 1])) {
				continue;
			}
			
			$width = (ord($font['cw'][$cid * 2]) << 8) + ord($font['cw'][$cid * 2 + 1]);
			if ($width == 65535) {
				$width = 0;
			}
			if ($cid > 255 && (!isset($font['subset'][$cid]) || !$font['subset'][$cid])) {
				continue;
			}
			if (!isset($font['dw']) || (isset($font['dw']) && $width != $font['dw'])) {
				if ($cid == ($prevcid + 1)) {
					if ($width == $prevwidth) {
						if ($width == $range[$rangeid][0]) {
							$range[$rangeid][] = $width;
						} else {
							array_pop($range[$rangeid]);
							// new range
							$rangeid = $prevcid;
							$range[$rangeid] = array();
							$range[$rangeid][] = $prevwidth;
							$range[$rangeid][] = $width;
						}
						$interval = true;
						$range[$rangeid]['interval'] = true;
					} else {
						if ($interval) {
							// new range
							$rangeid = $cid;
							$range[$rangeid] = array();
							$range[$rangeid][] = $width;
						} else {
							$range[$rangeid][] = $width;
						}
						$interval = false;
					}
				} else {
					$rangeid = $cid;
					$range[$rangeid] = array();
					$range[$rangeid][] = $width;
					$interval = false;
				}
				$prevcid = $cid;
				$prevwidth = $width;
			}
		}
		$prevk = -1;
		$nextk = -1;
		$prevint = false;
		foreach ($range as $k => $ws) {
			$cws = count($ws);
			if (($k == $nextk) AND ( !$prevint) AND ( (!isset($ws['interval'])) OR ( $cws < 4))) {
				if (isset($range[$k]['interval'])) {
					unset($range[$k]['interval']);
				}
				$range[$prevk] = array_merge($range[$prevk], $range[$k]);
				unset($range[$k]);
			} else {
				$prevk = $k;
			}
			$nextk = $k + $cws;
			if (isset($ws['interval'])) {
				if ($cws > 3) {
					$prevint = true;
				} else {
					$prevint = false;
				}
				unset($range[$k]['interval']);
				--$nextk;
			} else {
				$prevint = false;
			}
		}
		$w = '';
		foreach ($range as $k => $ws) {
			if (count(array_count_values($ws)) == 1) {
				$w .= ' ' . $k . ' ' . ($k + count($ws) - 1) . ' ' . $ws[0];
			} else {
				$w .= ' ' . $k . ' [ ' . implode(' ', $ws) . ' ]' . "\n";
			}
		}
		$this->_out('/W [' . $w . ' ]');
	}

	function _putimages() {
		foreach (array_keys($this->images) as $file) {
			$this->_putimage($this->images[$file]);
			unset($this->images[$file]['data']);
			unset($this->images[$file]['smask']);
		}
	}

	function _putimage(&$info) {
		$this->_newobj();
		$info['n'] = $this->n;
		$this->_out('<</Type /XObject');
		$this->_out('/Subtype /Image');
		$this->_out('/Width ' . $info['w']);
		$this->_out('/Height ' . $info['h']);
		if ($info['cs'] == 'Indexed')
			$this->_out('/ColorSpace [/Indexed /DeviceRGB ' . (strlen($info['pal']) / 3 - 1) . ' ' . ($this->n + 1) . ' 0 R]');
		else {
			$this->_out('/ColorSpace /' . $info['cs']);
			if ($info['cs'] == 'DeviceCMYK')
				$this->_out('/Decode [1 0 1 0 1 0 1 0]');
		}
		$this->_out('/BitsPerComponent ' . $info['bpc']);
		if (isset($info['f']))
			$this->_out('/Filter /' . $info['f']);
		if (isset($info['dp']))
			$this->_out('/DecodeParms <<' . $info['dp'] . '>>');
		if (isset($info['trns']) && is_array($info['trns'])) {
			$trns = '';
			for ($i = 0; $i < count($info['trns']); $i++)
				$trns .= $info['trns'][$i] . ' ' . $info['trns'][$i] . ' ';
			$this->_out('/Mask [' . $trns . ']');
		}
		if (isset($info['smask']))
			$this->_out('/SMask ' . ($this->n + 1) . ' 0 R');
		$this->_out('/Length ' . strlen($info['data']) . '>>');
		$this->_putstream($info['data']);
		$this->_out('endobj');
		// Soft mask
		if (isset($info['smask'])) {
			$dp = '/Predictor 15 /Colors 1 /BitsPerComponent 8 /Columns ' . $info['w'];
			$smask = array('w' => $info['w'], 'h' => $info['h'], 'cs' => 'DeviceGray', 'bpc' => 8, 'f' => $info['f'], 'dp' => $dp, 'data' => $info['smask']);
			$this->_putimage($smask);
		}
		// Palette
		if ($info['cs'] == 'Indexed') {
			$filter = ($this->compress) ? '/Filter /FlateDecode ' : '';
			$pal = ($this->compress) ? gzcompress($info['pal']) : $info['pal'];
			$this->_newobj();
			$this->_out('<<' . $filter . '/Length ' . strlen($pal) . '>>');
			$this->_putstream($pal);
			$this->_out('endobj');
		}
	}

	function _putxobjectdict() {
		foreach ($this->images as $image)
			$this->_out('/I' . $image['i'] . ' ' . $image['n'] . ' 0 R');
	}

	function _putresourcedict() {
		$this->_out('/ProcSet [/PDF /Text /ImageB /ImageC /ImageI]');
		$this->_out('/Font <<');
		foreach ($this->fonts as $font) {
			$this->_out('/F' . $font['i'] . ' ' . $font['n'] . ' 0 R');
		}
		$this->_out('>>');
		$this->_out('/XObject <<');
		$this->_putxobjectdict();
		$this->_out('>>');
	}

	function _putresources() {
		$this->_putfonts();
		$this->_putimages();
		// Resource dictionary
		$this->offsets[2] = strlen($this->buffer);
		$this->_out('2 0 obj');
		$this->_out('<<');
		$this->_putresourcedict();
		$this->_out('>>');
		$this->_out('endobj');
	}

	function _putinfo() {
		$this->_out('/Producer ' . $this->_textstring('tFPDF ' . tFPDF_VERSION));
		if (!empty($this->title))
			$this->_out('/Title ' . $this->_textstring($this->title));
		if (!empty($this->subject))
			$this->_out('/Subject ' . $this->_textstring($this->subject));
		if (!empty($this->author))
			$this->_out('/Author ' . $this->_textstring($this->author));
		if (!empty($this->keywords))
			$this->_out('/Keywords ' . $this->_textstring($this->keywords));
		if (!empty($this->creator))
			$this->_out('/Creator ' . $this->_textstring($this->creator));
		$this->_out('/CreationDate ' . $this->_textstring('D:' . @date('YmdHis')));
	}

	function _putcatalog() {
		$this->_out('/Type /Catalog');
		$this->_out('/Pages 1 0 R');
		if ($this->ZoomMode == 'fullpage')
			$this->_out('/OpenAction [3 0 R /Fit]');
		elseif ($this->ZoomMode == 'fullwidth')
			$this->_out('/OpenAction [3 0 R /FitH null]');
		elseif ($this->ZoomMode == 'real')
			$this->_out('/OpenAction [3 0 R /XYZ null null 1]');
		elseif (!is_string($this->ZoomMode))
			$this->_out('/OpenAction [3 0 R /XYZ null null ' . sprintf('%.2F', $this->ZoomMode / 100) . ']');
		if ($this->LayoutMode == 'single')
			$this->_out('/PageLayout /SinglePage');
		elseif ($this->LayoutMode == 'continuous')
			$this->_out('/PageLayout /OneColumn');
		elseif ($this->LayoutMode == 'two')
			$this->_out('/PageLayout /TwoColumnLeft');
	}

	function _putheader() {
		$this->_out('%PDF-' . $this->PDFVersion);
	}

	function _puttrailer() {
		$this->_out('/Size ' . ($this->n + 1));
		$this->_out('/Root ' . $this->n . ' 0 R');
		$this->_out('/Info ' . ($this->n - 1) . ' 0 R');
	}

	function _enddoc() {
		$this->_putheader();
		$this->_putpages();
		$this->_putresources();
		// Info
		$this->_newobj();
		$this->_out('<<');
		$this->_putinfo();
		$this->_out('>>');
		$this->_out('endobj');
		// Catalog
		$this->_newobj();
		$this->_out('<<');
		$this->_putcatalog();
		$this->_out('>>');
		$this->_out('endobj');
		// Cross-ref
		$o = strlen($this->buffer);
		$this->_out('xref');
		$this->_out('0 ' . ($this->n + 1));
		$this->_out('0000000000 65535 f ');
		for ($i = 1; $i <= $this->n; $i++)
			$this->_out(sprintf('%010d 00000 n ', $this->offsets[$i]));
		// Trailer
		$this->_out('trailer');
		$this->_out('<<');
		$this->_puttrailer();
		$this->_out('>>');
		$this->_out('startxref');
		$this->_out($o);
		$this->_out('%%EOF');
		$this->state = 3;
	}

// ********* NEW FUNCTIONS *********
// Converts UTF-8 strings to UTF16-BE.
	function UTF8ToUTF16BE($str, $setbom = true) {
		$outstr = "";
		if ($setbom) {
			$outstr .= "\xFE\xFF"; // Byte Order Mark (BOM)
		}
		$outstr .= mb_convert_encoding($str ?? '', 'UTF-16BE', 'UTF-8');
		return $outstr;
	}

// Converts UTF-8 strings to codepoints array
	function UTF8StringToArray($str) {
		$out = array();
		$len = strlen($str ?? '');
		for ($i = 0; $i < $len; $i++) {
			$uni = -1;
			$h = ord($str[$i]??'0');
			if ($h <= 0x7F)
				$uni = $h;
			elseif ($h >= 0xC2) {
				if (($h <= 0xDF) && ($i < $len - 1))
					$uni = ($h & 0x1F) << 6 | (ord($str[++$i]) & 0x3F);
				elseif (($h <= 0xEF) && ($i < $len - 2))
					$uni = ($h & 0x0F) << 12 | (ord($str[++$i]) & 0x3F) << 6 | (ord($str[++$i]) & 0x3F);
				elseif (($h <= 0xF4) && ($i < $len - 3))
					$uni = ($h & 0x0F) << 18 | (ord($str[++$i]) & 0x3F) << 12 | (ord($str[++$i]) & 0x3F) << 6 | (ord($str[++$i]) & 0x3F);
			}
			if ($uni >= 0) {
				$out[] = $uni;
			}
		}
		return $out;
	}

// End of class
}

/****************************************************************************
* Software: Tag Extraction Class                                            *
*           Extracts the tags and corresponding text from a string          *
* Version:  1.2                                                             *
* Date:     2005/12/08                                                      *
* Author:   Bintintan Andrei  -- klodoma@ar-sd.net                          *
*                                                                           *
* $Id$
*                                                                           *
*                                                                           *
* License:  Free for non-commercial use	                                    *
*                                                                           *
* You may use and modify this software as you wish.                         *
* PLEASE REPORT ANY BUGS TO THE AUTHOR. THANK YOU   
 * 
 * @package externe
 * @subpackage FPDF	                    *	                    *
****************************************************************************/
/**
 * Extracts the tags from a string
 * @package externe
 * @subpackage FPDF	
*/
class String_TAGS {
var $aTAGS;
var $aHREF;
var $iTagMaxElem;
	/**
    	Constructor
	*/
	function __construct($p_tagmax = 2){
		$this->aTAGS = array();
		$this->aHREF = array();
		$this->iTagMaxElem = $p_tagmax;
	}
	/** returnes true if $p_tag is a "<open tag>"
		@param 	$p_tag - tag string
                $p_array - tag array;
        @return true/false
	*/
    function OpenTag($p_tag, $p_array) {
        $aTAGS = &$this->aTAGS;
        $aHREF = &$this->aHREF;
        $maxElem = &$this->iTagMaxElem;
      
        if (!preg_match("/^<([a-zA-Z1-9]{1,$maxElem}) *(.*)>$/i", $p_tag, $reg)) 
			return false;
        $p_tag = $reg[1];
        $sHREF = array();
        if (isset($reg[2])) {
            preg_match_all("|([^ ]*)=[\"'](.*)[\"']|U", $reg[2], $out, PREG_PATTERN_ORDER);
            for ($i=0; $i<count($out[0]); $i++){
                $out[2][$i] = preg_replace("/(\"|')/i", "", $out[2][$i]);
                array_push($sHREF, array($out[1][$i], $out[2][$i]));
            }           
        }
        if (in_array($p_tag, $aTAGS)) 
			return false; //tag already opened
		
        if (in_array("</$p_tag>", $p_array)) {
        	array_push($aTAGS, $p_tag);
        	array_push($aHREF, $sHREF);
            return true;
        }
        return false;
    }
	/** returnes true if $p_tag is a "<close tag>"
		@param 	$p_tag - tag string
                $p_array - tag array;
        @return true/false
	*/
	function CloseTag($p_tag, $p_array) {
	    $aTAGS = &$this->aTAGS;
	    $aHREF = &$this->aHREF;
	    $maxElem = &$this->iTagMaxElem;
	    if (!preg_match("#^</([a-zA-Z1-9]{1,$maxElem})>$#", $p_tag, $reg)) 
			return false;
	    $p_tag = $reg[1];
	    if (in_array("$p_tag", $aTAGS)) {
	    	array_pop($aTAGS);
	    	array_pop($aHREF);
	    	return true;
		}
	    return false;
	}
    
    /**
    * @desc Expands the paramteres that are kept in Href field
    * @param        array of parameters
    * @return       string with concatenated results
    */
    
    function expand_parameters($pResult){
        $aTmp = $pResult['params'];
        if ($aTmp <> '')
            for ($i=0; $i<count($aTmp); $i++){
                $pResult[$aTmp[$i][0]] = $aTmp[$i][1];
            }
            
        unset($pResult['params']);
        
        return $pResult;
        
    }
	/** Optimieses the result of the tag
		In the result array there can be strings that are consecutive and have the same tag
		This is eliminated
		@param 	$result
		@return optimized array
	*/
	function optimize_tags($result) {
		if (count($result) == 0) 
			return $result;
		$res_result = array();
    	$current = $result[0];
    	$i = 1;
    	while ($i < count($result)) {
    		//if they have the same tag then we concatenate them
			if (($current['tag'] == $result[$i]['tag']) && ($current['params'] == $result[$i]['params'])){
				$current['text'] .= $result[$i]['text'];
			}else{
                $current = $this->expand_parameters($current);
				array_push($res_result, $current);
				$current = $result[$i];
			}
			$i++;
    	}
        $current = $this->expand_parameters($current);
    	array_push($res_result, $current);
        
    	return $res_result;
    }
   	/** Parses a string and returnes the result
		@param 	$p_str - string
        @return array (
        			array (string1, tag1),
        			array (string2, tag2)
        		)
	*/
	function get_tags($p_str){
	    $aTAGS = &$this->aTAGS;
	    $aHREF = &$this->aHREF;
	    $aTAGS = array();
	    $result = array();
		$reg = preg_split('/(<.*>)/U', $p_str, -1, PREG_SPLIT_DELIM_CAPTURE);
	    $sTAG = "";
	    $sHREF = "";
        foreach ($reg as $key => $val) {
	    	if ($val == "") continue;
	        if ($this->OpenTag($val,$reg)){
	            $sTAG = (($temp = end($aTAGS)) != NULL) ? $temp : "";
	            $sHREF = (($temp = end($aHREF)) != NULL) ? $temp : "";
	        } elseif($this->CloseTag($val, $reg)){
	            $sTAG = (($temp = end($aTAGS)) != NULL) ? $temp : "";
	            $sHREF = (($temp = end($aHREF)) != NULL) ? $temp : "";
	        } else {
	        	if ($val != "")
	        		array_push($result, array('text'=>$val, 'tag'=>$sTAG, 'params'=>$sHREF));
	        }
	    }//while
	    return $this->optimize_tags($result);
	}
}//class String_TAGS{

/* * **************************************************************************
 * Software: FPDF class extension                                            *
 *           Tag Based Multicell                                             *
 * Version:  1.0                                                             *
 * Date:     2005/12/08                                                      *
 * Author:   Bintintan Andrei  -- klodoma@ar-sd.net                          *
 *                                                                           *
 * License:  Free for non-commercial use                                     *
 *                                                                           *
 * You may use and modify this software as you wish.                         *
 * PLEASE REPORT ANY BUGS TO THE AUTHOR. THANK YOU                           *
 * ************************************************************************** */

class FPDF_TAG extends FPDF {

	var $wt_Current_Tag;
	var $wt_FontInfo; //tags font info
	var $wt_DataInfo; //parsed string data info
	var $wt_DataExtraInfo; //data extra INFO

	function _wt_Reset_Datas() {
		$this->wt_Current_Tag = "";
		$this->wt_DataInfo = array();
		$this->wt_DataExtraInfo = array(
			"LAST_LINE_BR" => "", //CURRENT LINE BREAK TYPE
			"CURRENT_LINE_BR" => "", //LAST LINE BREAK TYPE
			"TAB_WIDTH" => 10   //The tab WIDTH IS IN mm
		);

		$this->wt_DataExtraInfo["TAB_WIDTH"] *= (72 / 25.4) / $this->k;
	}

	/**
	  Sets current tag to specified style
	  @param        $tag - tag name
	  $family - text font family
	  $style - text style
	  $size - text size
	  $color - text color
	  @return     nothing
	 */
	function SetStyle($tag, $family, $style, $size, $color) {
		if ($tag == "ttags")
			$this->Error(">> ttags << is reserved TAG Name.");
		if ($tag == "")
			$this->Error("Empty TAG Name.");
		$tag = trim(strtoupper($tag));
		$this->TagStyle[$tag]['family'] = trim($family);
		$this->TagStyle[$tag]['style'] = trim($style);
		$this->TagStyle[$tag]['size'] = trim($size);
		$this->TagStyle[$tag]['color'] = trim($color);
	}

	/**
	  Sets current tag style as the current settings
	  - if the tag name is not in the tag list then de "DEFAULT" tag is saved.
	  This includes a fist call of the function SaveCurrentStyle()
	  @param        $tag - tag name
	  @return     nothing
	 */
	function ApplyStyle($tag) {
		$tag = trim(strtoupper($tag));

		if ($this->wt_Current_Tag == $tag)
			return;

		if (($tag == "") || (!isset($this->TagStyle[$tag])))
			$tag = "DEFAULT";

		$this->wt_Current_Tag = $tag;

		$style = &$this->TagStyle[$tag];

		if (isset($style) && is_array($style)) {
			$this->SetFont($style['family'], $style['style'], $style['size']);
			//this is textcolor in FPDF format
			if (isset($style['textcolor_fpdf'])) {
				$this->TextColor = $style['textcolor_fpdf'];
				$this->ColorFlag = ($this->FillColor != $this->TextColor);
			} else {
				if ($style['color'] <> "") {//if we have a specified color
					$temp = explode(",", $style['color']);
					$this->SetTextColor($temp[0], $temp[1], $temp[2]);
				}//fi
			}
		}
	}

	/**
	  Save the current settings as a tag default style under the DEFAUTLT tag name
	  @param        none
	  @return     nothing
	 */
	function SaveCurrentStyle() {
		$this->TagStyle['DEFAULT']['family'] = $this->FontFamily;
		$this->TagStyle['DEFAULT']['style'] = $this->FontStyle;
		$this->TagStyle['DEFAULT']['size'] = $this->FontSizePt;
		$this->TagStyle['DEFAULT']['textcolor_fpdf'] = $this->TextColor;
		$this->TagStyle['DEFAULT']['color'] = "";
	}

	/**
	  Divides $this->wt_DataInfo and returnes a line from this variable
	  @param        $w - Width of the text
	  @return     $aLine = array() -> contains informations to draw a line
	 */
	function MakeLine($w) {
		$aDataInfo = &$this->wt_DataInfo;
		$aExtraInfo = &$this->wt_DataExtraInfo;

		//last line break >> current line break
		$aExtraInfo['LAST_LINE_BR'] = $aExtraInfo['CURRENT_LINE_BR'];
		$aExtraInfo['CURRENT_LINE_BR'] = "";

		if ($w == 0)
			$w = $this->w - $this->rMargin - $this->x;

		$wmax = ($w - 2 * $this->cMargin) * 1000; //max width

		$aLine = array(); //this will contain the result
		$return_result = false; //if break and return result
		$reset_spaces = false;

		$line_width = 0; //line string width
		$total_chars = 0; //total characters included in the result string
		$space_count = 0; //numer of spaces in the result string
		$fw = &$this->wt_FontInfo; //font info array
				
		$last_sepch = ""; //last separator character
				
		foreach ($aDataInfo as $key => $val) {
			$firstElement = 0;
			$s = $val['text'];	
			
			$tag = &$val['tag'];
			
			$s_lenght = mb_strlen($s);
			
			$i = 0; //from where is the string remain
			$j = 0; //untill where is the string good to copy -- leave this == 1->> copy at least one character!!!	
			$str = "";
			$s_width = 0; //string width
			$last_sep = -1; //last separator position
			$last_sepwidth = 0;
			$last_sepch_width = 0;
			$ante_last_sep = -1; //ante last separator position
			$spaces = 0;
			$sTemp = $s;			
			while ($i < $s_lenght) {
//				$c = $s[$i];
				$c = mb_substr($s, $i, 1);

				if ($c == "\n") {//Explicit line break
					$i++; //ignore/skip this caracter
					$aExtraInfo['CURRENT_LINE_BR'] = "BREAK";
					$return_result = true;
					$reset_spaces = true;
					break;
				}

				//space
				if ($c == " ") {
					$space_count++; //increase the number of spaces
					$spaces++;
				}

				//    Font Width / Size Array
				if (!isset($fw[$tag]) || ($tag == "")) {
					//if this font was not used untill now,					
					$this->ApplyStyle($tag);
					$fw[$tag]['s'] = $this->FontSize; //size
				}				
				
				$size = 747;
				$angle = 0;
				$dimension = imagettfbbox($size, $angle, $this->CurrentFont['ttffile'], $c);
				$c_width = $dimension[2];			
				
				$char_width = $c_width * $fw[$tag]['s'];
																
				//separators
				if (is_int(strpos(" ,.:;", $c))) {

					$ante_last_sep = $last_sep;
					$ante_last_sepch = $last_sepch;
					$ante_last_sepwidth = $last_sepwidth;
					$ante_last_sepch_width = $last_sepch_width;

					$last_sep = $i; //last separator position
					$last_sepch = $c; //last separator char
					$last_sepch_width = $char_width; //last separator char
					$last_sepwidth = $s_width;
				}

				if ($c == "\t") {
					$c = "";
					$char_width = $aExtraInfo['TAB_WIDTH'] * 1000;
				}

				$line_width += $char_width;

				if ($line_width > $wmax) {//Automatic line break
					$aExtraInfo['CURRENT_LINE_BR'] = "AUTO";
					if ($total_chars == 0) {
						/* This MEANS that the $w (width) is lower than a char width...
						  Put $i and $j to 1 ... otherwise infinite while */
						$i = 1;
						$j = 1;
					}//fi
					if ($last_sep <> -1) {
						//we have a separator in this tag!!!
						//untill now there one separator
						if (($last_sepch == $c) && ($last_sepch != " ") && ($ante_last_sep <> -1)) {
							/*    this is the last character and it is a separator, if it is a space the leave it...
							  Have to jump back to the las separator... even a space
							 */
							$last_sep = $ante_last_sep;
							$last_sepch = $ante_last_sepch;
							$last_sepwidth = $ante_last_sepwidth;
						}

						if ($last_sepch == " ") {
							$j = $last_sep; //just ignore the last space (it is at end of line)
							$i = $last_sep + 1;							
							if ($spaces > 0)
								$spaces--;
							$s_width = $last_sepwidth;
						} else {
							$j = $last_sep + 1;
							$i = $last_sep + 1;							
							#$s_width = $last_sepwidth + $fw[$tag]['w'][$last_sepch] * $fw[$tag]['s'];
							$s_width = $last_sepwidth + $last_sepch_width;
						}
					} elseif (count($aLine) > 0) {
						//we have elements in the last tag!!!!
						if ($last_sepch == " ") {//the last tag ends with a space, have to remove it
							$temp = &$aLine[count($aLine) - 1];


							if ($temp['text'][strlen($temp['text']) - 1] == " ") {

								$temp['text'] = mb_substr($temp['text'], 0, mb_strlen($temp['text']) - 1);
								if(isset($fw[$temp['tag']]['w']))
								    $temp['width'] -= $fw[$temp['tag']]['w'][" "] * $fw[$temp['tag']]['s'];
								$temp['spaces'] --;

								//imediat return from this function
								break 2;
							} else {
								die($temp['text']);
								die("should not be!!!");
							}//fi
						}//fi
					}//fi else

					$return_result = true;
					break;
				}//fi - Auto line break
				//increase the string width ONLY when it is added!!!!
				$s_width += $char_width;
				
				$i++;
				$j = $i;
				$total_chars++;				
				if (isset($s[$i]) && $s[$i] != "\t") {
					$sTemp .= $s[$i];
				}
			}//while
			$s = $sTemp;
			
			$str = mb_substr($s, 0, $j);
			$sTmpStr = &$aDataInfo[$firstElement]['text'];	
			$sTmpStr = mb_substr($sTmpStr, $i, mb_strlen($sTmpStr));

			if (($sTmpStr == "") || ($sTmpStr === FALSE)) {//empty
				/**
				 * remove the first element from array
				 */
				array_shift($aDataInfo);
			}

			if ($val['text'] == $str) {
				
			}
			$href = '';
			if (isset($val['href'])) {
				$href = $val['href'];
			}
			//we have a partial result
			array_push($aLine, array(
				'text' => $str,
				'tag' => $val['tag'],
				'href' => $href,
				'width' => $s_width,
				'spaces' => $spaces
			));

			if ($return_result)
				break; //break this for
		}//foreach
		// Check the first and last tag -> if first and last caracters are " " space remove them!!!"

        if ((count($aLine) > 0) && ($aExtraInfo['LAST_LINE_BR'] == "AUTO")) {
            //first tag
            $temp = &$aLine[0];
            $tamanhoTexto = mb_strlen($temp['text']);

            if (($tamanhoTexto > 0) && ($temp['text'][0] == " ") && isset($fw[$temp['tag']]['w'])) {
                $temp['text'] = substr($temp['text'], 1, $tamanhoTexto);
                $temp['width'] -= $fw[$temp['tag']]['w'][" "] * $fw[$temp['tag']]['s'];
                $temp['spaces'] --;
            }

            //last tag
            $temp = &$aLine[count($aLine) - 1];

            //reatribui pois pode ter sido modificado anteriormente
            $tamanhoTexto = mb_strlen($temp['text']);
            if (($tamanhoTexto > 0) && ($temp['text'][$tamanhoTexto - 1] == " ") && isset($fw[$temp['tag']]['w'])) {
                $temp['text'] = substr($temp['text'], 0, $tamanhoTexto - 1);
                $temp['width'] -= $fw[$temp['tag']]['w'][" "] * $fw[$temp['tag']]['s'];
                $temp['spaces'] --;
            }
        }

		if ($reset_spaces) {//this is used in case of a "Explicit Line Break"
			//put all spaces to 0 so in case of "J" align there is no space extension
			for ($k = 0; $k < count($aLine); $k++)
				$aLine[$k]['spaces'] = 0;
		}//fi

		return $aLine;
	}

	/**
	  Draws a MultiCell with TAG recognition parameters
	  @param        $w - with of the cell
	  $h - height of the cell
	  $pStr - string to be printed
	  $border - border
	  $align    - align
	  $fill - fill

	  These paramaters are the same and have the same behavior as at Multicell function
	  @return     nothing
	 */
	function MultiCellTag($w, $h, $pStr, $border = 0, $align = 'J', $fill = 0) {
		$this->SaveCurrentStyle();
		$this->_wt_Reset_Datas();
		$pStr = str_replace("\t", "<ttags>\t</ttags>", $pStr??'');
		$pStr = str_replace("\r", "", $pStr);

		//initialize the String_TAGS class
		$sWork = new String_TAGS(5);

		//get the string divisions by tags
		$this->wt_DataInfo = $sWork->get_tags($pStr);

		$b = $b1 = $b2 = $b3 = ''; //borders
		//save the current X position, we will have to jump back!!!!
		$startX = $this->GetX();

		if ($border) {
			if ($border == 1) {
				$border = 'LTRB';
				$b1 = 'LRT'; //without the bottom
				$b2 = 'LR'; //without the top and bottom
				$b3 = 'LRB'; //without the top
			} else {
				$b2 = '';
				if (is_int(strpos($border, 'L')))
					$b2 .= 'L';
				if (is_int(strpos($border, 'R')))
					$b2 .= 'R';
				$b1 = is_int(strpos($border, 'T')) ? $b2 . 'T' : $b2;
				$b3 = is_int(strpos($border, 'B')) ? $b2 . 'B' : $b2;
			}

			//used if there is only one line
			$b = '';
			$b .= is_int(strpos($border, 'L')) ? 'L' : "";
			$b .= is_int(strpos($border, 'R')) ? 'R' : "";
			$b .= is_int(strpos($border, 'T')) ? 'T' : "";
			$b .= is_int(strpos($border, 'B')) ? 'B' : "";
		}

		$first_line = true;
		$last_line = !(count($this->wt_DataInfo) > 0);

		while (!$last_line) {
			if ($fill == 1) {
				//fill in the cell at this point and write after the text without filling
				$this->Cell($w, $h, "", 0, 0, "", 1);
				$this->SetX($startX); //restore the X position
			}

			//make a line
			$str_data = $this->MakeLine($w);

			//check for last line
			$last_line = !(count($this->wt_DataInfo) > 0);

			if ($last_line && ($align == "J")) {//do not Justify the Last Line
				$align = "L";
			}

			//outputs a line
			$this->PrintLine($w, $h, $str_data, $align);


			//see what border we draw:
			if ($first_line && $last_line) {
				//we have only 1 line
				$real_brd = $b;
			} elseif ($first_line) {
				$real_brd = $b1;
			} elseif ($last_line) {
				$real_brd = $b3;
			} else {
				$real_brd = $b2;
			}

			if ($first_line)
				$first_line = false;

			//draw the border and jump to the next line
			$this->SetX($startX); //restore the X
			$this->Cell($w, $h, "", $real_brd, 2);
		}//while(! $last_line){
		//APPLY THE DEFAULT STYLE
		$this->ApplyStyle("DEFAULT");

		$this->x = $this->lMargin;
	}

	/**
	  This method returns the number of lines that will a text ocupy on the specified width
	  @param        $w - with of the cell
	  $pStr - string to be printed
	  @return     $nb_lines - number of lines
	 */
	function NbLines($w, $pStr) {
		$this->SaveCurrentStyle();
		$this->_wt_Reset_Datas();

		$pStr = str_replace("\t", "<ttags>\t</ttags>", $pStr??'');
		$pStr = str_replace("\r", "", $pStr);
		
		//initialize the String_TAGS class
		$sWork = new String_TAGS(5);

		//get the string divisions by tags
		$this->wt_DataInfo = $sWork->get_tags($pStr);

		$first_line = true;
		$last_line = !(count($this->wt_DataInfo) > 0);
		$nb_lines = 0;
		
		while (!$last_line) {
			$str_data = $this->MakeLine($w);			
			//check for last line
			$last_line = !(count($this->wt_DataInfo) > 0);

			if ($first_line){
				$first_line = false;
			}

			$nb_lines++;
		}

		$this->ApplyStyle("DEFAULT");

		return $nb_lines;
	}

	/**
	  Draws a line returned from MakeLine function
	  @param        $w - with of the cell
	  $h - height of the cell
	  $aTxt - array from MakeLine
	  $align - text align
	  @return     nothing
	 */
	function PrintLine($w, $h, $aTxt, $align = 'J') {
		if ($w == 0)
			$w = $this->w - $this->rMargin - $this->x;

		$wmax = $w; //Maximum width

		$total_width = 0; //the total width of all strings
		$total_spaces = 0; //the total number of spaces

		$nr = count($aTxt); //number of elements

		for ($i = 0; $i < $nr; $i++) {
			$total_width += ($aTxt[$i]['width'] / 1000);
			$total_spaces += $aTxt[$i]['spaces'];
		}

		//default
		$w_first = $this->cMargin;

		switch ($align) {
			case 'J':
				if ($total_spaces > 0)
					$extra_space = ($wmax - 2 * $this->cMargin - $total_width) / $total_spaces;
				else
					$extra_space = 0;
				break;
			case 'L':
				break;
			case 'C':
				$w_first = ($wmax - $total_width) / 2;
				break;
			case 'R':
				$w_first = $wmax - $total_width - $this->cMargin;
				break;
		}

		// Output the first Cell
		if ($w_first != 0) {
			$this->Cell($w_first, $h, "", 0, 0, "L", 0);
		}

		$last_width = $wmax - $w_first;

		foreach ($aTxt as $key => $val) {

			//apply current tag style
			$this->ApplyStyle($val['tag']);

			//If > 0 then we will move the current X Position
			$extra_X = 0;

			//string width
			$width = $this->GetStringWidth($val['text']);
			$width = $val['width'] / 1000;

			if ($width == 0)
				continue; // No width jump over!!!

			if ($align == 'J') {
				if ($val['spaces'] < 1)
					$temp_X = 0;
				else
					$temp_X = $extra_space;

				$this->ws = $temp_X;

				$this->_out(sprintf('%.3f Tw', $temp_X * $this->k));

				$extra_X = $extra_space * $val['spaces']; //increase the extra_X Space
			} else {
				$this->ws = 0;
				$this->_out('0 Tw');
			}//fi
			//Output the Text/Links
			$this->Cell($width, $h, $val['text'], 0, 0, "C", 0, $val['href']);

			$last_width -= $width; //last column width

			if ($extra_X != 0) {
				$this->SetX($this->GetX() + $extra_X);
				$last_width -= $extra_X;
			}//fi
		}

		if ($last_width != 0) {
			$this->Cell($last_width, $h, "", 0, 0, "", 0);
		}
	}

}

class MultiTable extends FPDF_TAG {

	var $widths;
	var $aligns;
	var $color;
	var $estilo;
	var $colorDefault;
	var $fntDefault;
	var $fntBoldDefault;
	var $fontDefault;

	function __construct($orientation = 'P', $unit = 'mm', $format = 'A4') {
		parent::__construct($orientation, $unit, $format);
	}

	function SetWidths($w) {
		//Set the array of column widths
		$this->widths = $w;
	}

	function SetColor($c) {
		//Set the array of column widths
		$this->color = $c;
	}

	function GetColor() {
		//Set the array of column widths
		return $this->color;
	}

	function SetEstilo($c) {
		//Set the array of column widths
		$this->estilo = $c;
	}

	function SetFntDefault($c) {
		$this->fntDefault = $c;
	}

	function SetFntBoldDefault($c) {
		$this->fntBoldDefault = $c;
	}

	function SetcolorDefault($c) {
		//Set the array of column widths
		$this->colorDefault = $c;
	}

	function SetAligns($a) {
		//Set the array of column alignments
		$this->aligns = $a;
	}

	function Row($data, $fill = 0, $lineh = 5, $with_border = true) {
		//Calculate the height of the row
		$nb = 0;				
		for ($i = 0; $i < count($data); $i++) {
			$withs = $this->widths[$i]??0;
			$dataLine = $data[$i];
			$nb = max($nb, $this->NbLines($withs, $dataLine));
		}
		$h = $lineh * $nb;		
		//Issue a page break first if needed
		$this->CheckPageBreak($h);
		//Draw the cells of the row
		for ($i = 0; $i < count($data); $i++) {
			$w = isset($this->widths[$i])?$this->widths[$i]:0;
			$a = isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
			//Save the current position
			$x = $this->GetX();
			$y = $this->GetY();
			//Draw the border
			if ($with_border) {
				$this->Rect($x, $y, $w, $h, 'DF');
			}
			//Print the text
			$this->MultiCellTag($w, $lineh, $data[$i], 0, $a, $fill);
			//Put the position to the right of the cell
			$this->SetXY($x + $w, $y);
		}
		//Go to the next line
		$this->Ln($h);
	}

	function RowColor($data) {
		//Calculate the height of the row
		$nb = 0;
		for ($i = 0; $i < count($data); $i++) {
			$nb = max($nb, $this->NbLines($this->widths[$i], $data[$i]));
		}
		$h = 3.5 * $nb;
		//Issue a page break first if needed
		$this->CheckPageBreak($h);
		//Draw the cells of the row
		for ($i = 0; $i < count($data); $i++) {
			$w = $this->widths[$i];
			$a = isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
			//Save the current position
			$x = $this->GetX();
			$y = $this->GetY();
			//Draw the border
			$this->Rect($x, $y, $w, $h, 'DF');
			//Print the text
			if (isset($this->color[$i])) {
				$this->SetTextColorArray($this->color[$i]);
			} else {
				$this->SetTextColorArray($this->colorDefault);
			}
			$this->MultiCellTag($w, 3.5, $data[$i], 0, $a);
			//Put the position to the right of the cell
			$this->SetXY($x + $w, $y);
		}
		//Go to the next line
		$this->Ln($h);
	}

	function RowEstilo($data) {
		//Calculate the height of the row
		$nb = 0;
		for ($i = 0; $i < count($data); $i++) {
			$nb = max($nb, $this->NbLines($this->widths[$i], $data[$i]));
		}
		$h = 5 * $nb;
		//Issue a page break first if needed
		$this->CheckPageBreak($h);
		//Draw the cells of the row
		for ($i = 0; $i < count($data); $i++) {
			$w = $this->widths[$i];
			$a = isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
			//Save the current position
			$x = $this->GetX();
			$y = $this->GetY();
			//Draw the border
			$this->Rect($x, $y, $w, $h, 'DF');
			//Print the text
			if (isset($this->estilo[$i])) {
				$this->SetFont($this->fntBoldDefault[0], $this->fntBoldDefault[1], $this->fntBoldDefault[2]);
			} else {
				$this->SetFont($this->fntDefault[0], $this->fntDefault[1], $this->fntDefault[2]);
			}
			$this->MultiCellTag($w, 5, $data[$i], 0, $a);
			//Put the position to the right of the cell
			$this->SetXY($x + $w, $y);
		}
		//Go to the next line
		$this->Ln($h);
	}

	function CheckPageBreak($h) {
		//If the height h would cause an overflow, add a new page immediately
		if ($this->GetY() + $h > $this->PageBreakTrigger) {
			$this->AddPage($this->CurOrientation);
		}
	}

	/* function NbLines($w,$txt)
	  {
	  //Computes the number of lines a MultiCell of width w will take
	  $cw=&$this->CurrentFont['cw'];
	  if($w==0)
	  {
	  $w=$this->w-$this->rMargin-$this->x;
	  }
	  $wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
	  $s=str_replace("\r",'',$txt);
	  $nb=strlen($s);
	  if($nb>0 and $s[$nb-1]=="\n")
	  {
	  $nb--;
	  }
	  $sep=-1;
	  $i=0;
	  $j=0;
	  $l=0;
	  $nl=1;
	  while($i<$nb)
	  {
	  $c=$s[$i];
	  if($c=="\n")
	  {
	  $i++;
	  $sep=-1;
	  $j=$i;
	  $l=0;
	  $nl++;
	  continue;
	  }
	  if($c==' ')
	  {
	  $sep=$i;
	  }
	  $l+=$cw[$c];
	  if($l>$wmax)
	  {
	  if($sep==-1)
	  {
	  if($i==$j)
	  {
	  $i++;
	  }
	  }
	  else
	  {
	  $i=$sep+1;
	  }
	  $sep=-1;
	  $j=$i;
	  $l=0;
	  $nl++;
	  }
	  else
	  {
	  $i++;
	  }
	  }
	  return $nl;
	  } */
}

class PDF_PageGroup extends MultiTable {

	var $NewPageGroup;   // variable indicating whether a new group was requested
	var $PageGroups;  // variable containing the number of pages of the groups
	var $CurrPageGroup;  // variable containing the alias of the current page group

	// create a new page group; call this before calling AddPage()

	function StartPageGroup() {
		$this->NewPageGroup = true;
	}

	// current page in the group
	function GroupPageNo() {
		return $this->PageGroups[$this->CurrPageGroup];
	}

	// alias of the current page group -- will be replaced by the total number of pages in this group
	function PageGroupAlias() {
		return $this->CurrPageGroup;
	}

	function _beginpage($orientation, $size) {
		parent::_beginpage($orientation, '');
		if ($this->NewPageGroup) {
			// start a new group
			$n = sizeof($this->PageGroups) + 1;
			$alias = "{nb$n}";
			$this->PageGroups[$alias] = 1;
			$this->CurrPageGroup = $alias;
			$this->NewPageGroup = false;
		} elseif ($this->CurrPageGroup)
			$this->PageGroups[$this->CurrPageGroup] ++;
	}

	function _putpages() {
		$nb = $this->page;
		if (!empty($this->PageGroups)) {
			// do page number replacement
			foreach ($this->PageGroups as $k => $v) {
				for ($n = 1; $n <= $nb; $n++) {
					$this->pages[$n] = str_replace($k, $v, $this->pages[$n]);
				}
			}
		}
		parent::_putpages();
	}

}

/* * **************************************************************************
 * Software: PDF                                                 *
 * Version:  1.02                                                            *
 * Date:     2005/05/08                                                      *
 * Author:   Klemen VODOPIVEC                                                *
 * License:  Freeware                                                        *
 *                                                                           *
 * You may use and modify this software as you wish as stated in original    *
 * FPDF package.                                                             *
 *                                                                           *
 * Thanks: Cpdf (http://www.ros.co.nz/pdf) was my working sample of how to   *
 * implement protection in pdf.                                              *
 * ************************************************************************** */

class PDF extends PDF_PageGroup {

	var $encrypted; //whether document is protected
	var $Uvalue; //U entry in pdf document
	var $Ovalue; //O entry in pdf document
	var $Pvalue; //P entry in pdf document
	var $enc_obj_id;   //encryption object id
	var $last_rc4_key; //last RC4 key encrypted (cached for optimisation)
	var $last_rc4_key_c;  //last RC4 computed key
	var $padding;
	var $TagStyle;

	function __construct($orientation = 'P', $unit = 'mm', $format = 'A4') {
		parent::__construct($orientation, $unit, $format);

		$this->encrypted = false;
		$this->last_rc4_key = '';
		$this->padding = "\x28\xBF\x4E\x5E\x4E\x75\x8A\x41\x64\x00\x4E\x56\xFF\xFA\x01\x08" .
			"\x2E\x2E\x00\xB6\xD0\x68\x3E\x80\x2F\x0C\xA9\xFE\x64\x53\x69\x7A";
	}

	/**
	 * Function to set permissions as well as user and owner passwords
	 *
	 * - permissions is an array with values taken from the following list:
	 *   copy, print, modify, annot-forms
	 *   If a value is present it means that the permission is granted
	 * - If a user password is set, user will be prompted before document is opened
	 * - If an owner password is set, document can be opened in privilege mode with no
	 *   restriction if that password is entered
	 */
	function SetProtection($permissions = array(), $user_pass = '', $owner_pass = null) {
		$options = array('print' => 4, 'modify' => 8, 'copy' => 16, 'annot-forms' => 32);
		$protection = 192;
		foreach ($permissions as $permission) {
			if (!isset($options[$permission]))
				$this->Error('Incorrect permission: ' . $permission);
			$protection += $options[$permission];
		}
		if ($owner_pass === null)
			$owner_pass = uniqid(rand());
		$this->encrypted = true;
		$this->_generateencryptionkey($user_pass, $owner_pass, $protection);
	}

	/*	 * **************************************************************************
	 *                                                                           *
	 *                              Private methods                              *
	 *                                                                           *
	 * ************************************************************************** */

	function _putstream($s) {
		if ($this->encrypted) {
			$s = $this->_RC4($this->_objectkey($this->n), $s);
		}
		parent::_putstream($s);
	}

	function _textstring($s) {
		if ($this->encrypted) {
			$s = $this->_RC4($this->_objectkey($this->n), $s);
		}
		return parent::_textstring($s);
	}

	function _objectkey($n) {
		return substr($this->_md5_16($this->encryption_key . pack('VXxx', $n)), 0, 10);
	}

	function _escape($s) {
		$s = str_replace('\\', '\\\\', $s);
		$s = str_replace(')', '\\)', $s);
		$s = str_replace('(', '\\(', $s);
		$s = str_replace("\r", '\\r', $s);
		return $s;
	}

	function _putresources() {
		parent::_putresources();
		if ($this->encrypted) {
			$this->_newobj();
			$this->enc_obj_id = $this->n;
			$this->_out('<<');
			$this->_putencryption();
			$this->_out('>>');
			$this->_out('endobj');
		}
	}

	function _putencryption() {
		$this->_out('/Filter /Standard');
		$this->_out('/V 1');
		$this->_out('/R 2');
		$this->_out('/O (' . $this->_escape($this->Ovalue) . ')');
		$this->_out('/U (' . $this->_escape($this->Uvalue) . ')');
		$this->_out('/P ' . $this->Pvalue);
	}

	function _puttrailer() {
		parent::_puttrailer();
		if ($this->encrypted) {
			$this->_out('/Encrypt ' . $this->enc_obj_id . ' 0 R');
			$this->_out('/ID [()()]');
		}
	}

	function _RC4($key, $text) {
		if ($this->last_rc4_key != $key) {
			$k = str_repeat($key, 256 / strlen($key) + 1);
			$rc4 = range(0, 255);
			$j = 0;
			for ($i = 0; $i < 256; $i++) {
				$t = $rc4[$i];
				$j = ($j + $t + ord($k[$i])) % 256;
				$rc4[$i] = $rc4[$j];
				$rc4[$j] = $t;
			}
			$this->last_rc4_key = $key;
			$this->last_rc4_key_c = $rc4;
		} else {
			$rc4 = $this->last_rc4_key_c;
		}

		$len = strlen($text);
		$a = 0;
		$b = 0;
		$out = '';
		for ($i = 0; $i < $len; $i++) {
			$a = ($a + 1) % 256;
			$t = $rc4[$a];
			$b = ($b + $t) % 256;
			$rc4[$a] = $rc4[$b];
			$rc4[$b] = $t;
			$k = $rc4[($rc4[$a] + $rc4[$b]) % 256];
			$out .= chr(ord($text[$i]) ^ $k);
		}

		return $out;
	}

	function _md5_16($string) {
		return pack('H*', md5($string));
	}

	function _Ovalue($user_pass, $owner_pass) {
		$tmp = $this->_md5_16($owner_pass);
		$owner_RC4_key = substr($tmp, 0, 5);
		return $this->_RC4($owner_RC4_key, $user_pass);
	}

	function _Uvalue() {
		return $this->_RC4($this->encryption_key, $this->padding);
	}

	function _generateencryptionkey($user_pass, $owner_pass, $protection) {
		// Pad passwords
		$user_pass = substr($user_pass . $this->padding, 0, 32);
		$owner_pass = substr($owner_pass . $this->padding, 0, 32);
		// Compute O value
		$this->Ovalue = $this->_Ovalue($user_pass, $owner_pass);
		// Compute encyption key
		$tmp = $this->_md5_16($user_pass . $this->Ovalue . chr($protection) . "\xFF\xFF\xFF");
		$this->encryption_key = substr($tmp, 0, 5);
		// Compute U value
		$this->Uvalue = $this->_Uvalue();
		// Compute P value
		$this->Pvalue = -(($protection ^ 255) + 1);
	}

	function Sector($xc, $yc, $r, $a, $b, $style = 'FD', $cw = true, $o = 90) {
		$d0 = $a - $b;
		if ($cw) {
			$d = $b;
			$b = $o - $a;
			$a = $o - $d;
		} else {
			$b += $o;
			$a += $o;
		}
		while ($a < 0)
			$a += 360;
		while ($a > 360)
			$a -= 360;
		while ($b < 0)
			$b += 360;
		while ($b > 360)
			$b -= 360;
		if ($a > $b)
			$b += 360;
		$b = $b / 360 * 2 * M_PI;
		$a = $a / 360 * 2 * M_PI;
		$d = $b - $a;
		if ($d == 0 && $d0 != 0)
			$d = 2 * M_PI;
		$k = $this->k;
		$hp = $this->h;
		if (sin($d / 2))
			$MyArc = 4 / 3 * (1 - cos($d / 2)) / sin($d / 2) * $r;
		else
			$MyArc = 0;
		//first put the center
		$this->_out(sprintf('%.2F %.2F m', ($xc) * $k, ($hp - $yc) * $k));
		//put the first point
		$this->_out(sprintf('%.2F %.2F l', ($xc + $r * cos($a)) * $k, (($hp - ($yc - $r * sin($a))) * $k)));
		//draw the arc
		if ($d < M_PI / 2) {
			$this->_Arc($xc + $r * cos($a) + $MyArc * cos(M_PI / 2 + $a),
				$yc - $r * sin($a) - $MyArc * sin(M_PI / 2 + $a),
				$xc + $r * cos($b) + $MyArc * cos($b - M_PI / 2),
				$yc - $r * sin($b) - $MyArc * sin($b - M_PI / 2),
				$xc + $r * cos($b),
				$yc - $r * sin($b)
			);
		} else {
			$b = $a + $d / 4;
			$MyArc = 4 / 3 * (1 - cos($d / 8)) / sin($d / 8) * $r;
			$this->_Arc($xc + $r * cos($a) + $MyArc * cos(M_PI / 2 + $a),
				$yc - $r * sin($a) - $MyArc * sin(M_PI / 2 + $a),
				$xc + $r * cos($b) + $MyArc * cos($b - M_PI / 2),
				$yc - $r * sin($b) - $MyArc * sin($b - M_PI / 2),
				$xc + $r * cos($b),
				$yc - $r * sin($b)
			);
			$a = $b;
			$b = $a + $d / 4;
			$this->_Arc($xc + $r * cos($a) + $MyArc * cos(M_PI / 2 + $a),
				$yc - $r * sin($a) - $MyArc * sin(M_PI / 2 + $a),
				$xc + $r * cos($b) + $MyArc * cos($b - M_PI / 2),
				$yc - $r * sin($b) - $MyArc * sin($b - M_PI / 2),
				$xc + $r * cos($b),
				$yc - $r * sin($b)
			);
			$a = $b;
			$b = $a + $d / 4;
			$this->_Arc($xc + $r * cos($a) + $MyArc * cos(M_PI / 2 + $a),
				$yc - $r * sin($a) - $MyArc * sin(M_PI / 2 + $a),
				$xc + $r * cos($b) + $MyArc * cos($b - M_PI / 2),
				$yc - $r * sin($b) - $MyArc * sin($b - M_PI / 2),
				$xc + $r * cos($b),
				$yc - $r * sin($b)
			);
			$a = $b;
			$b = $a + $d / 4;
			$this->_Arc($xc + $r * cos($a) + $MyArc * cos(M_PI / 2 + $a),
				$yc - $r * sin($a) - $MyArc * sin(M_PI / 2 + $a),
				$xc + $r * cos($b) + $MyArc * cos($b - M_PI / 2),
				$yc - $r * sin($b) - $MyArc * sin($b - M_PI / 2),
				$xc + $r * cos($b),
				$yc - $r * sin($b)
			);
		}
		//terminate drawing
		if ($style == 'F')
			$op = 'f';
		elseif ($style == 'FD' || $style == 'DF')
			$op = 'b';
		else
			$op = 's';
		$this->_out($op);
	}

	function _Arc($x1, $y1, $x2, $y2, $x3, $y3) {
		$h = $this->h;
		$this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c',
				$x1 * $this->k,
				($h - $y1) * $this->k,
				$x2 * $this->k,
				($h - $y2) * $this->k,
				$x3 * $this->k,
				($h - $y3) * $this->k));
	}

	public $legends;
	public $wLegend;
	public $sum;
	public $NbVal;

	function PieChart($w, $h, $data, $format, $colors = null) {
		$this->SetFont(self::FONT_COURIER, '', 10);
		$this->SetLegends($data, $format);

		$XPage = $this->GetX();
		$YPage = $this->GetY();
		$margin = 2;
		$hLegend = 5;
		$radius = min($w - $margin * 4 - $hLegend - $this->wLegend, $h - $margin * 2);
		$radius = floor($radius / 2);
		$XDiag = $XPage + $margin + $radius;
		$YDiag = $YPage + $margin + $radius;
		if ($colors == null) {
			for ($i = 0; $i < $this->NbVal; $i++) {
				$gray = $i * intval(255 / $this->NbVal);
				$colors[$i] = array($gray, $gray, $gray);
			}
		}

//Sectors
		$this->SetLineWidth(0.2);
		$angleStart = 0;
		$angleEnd = 0;
		$i = 0;
		foreach ($data as $val) {
			if ($this->sum == 0) {//when the sun is zero, use 0.01 to create a pie
				$this->sum = 0.01;
			}
			$angle = floor(($val * 360) / doubleval($this->sum));
			if ($angle != 0) {
				$angleEnd = $angleStart + $angle;
				$this->SetFillColor($colors[$i][0], $colors[$i][1], $colors[$i][2]);
				$this->Sector($XDiag, $YDiag, $radius, $angleStart, $angleEnd);
				$angleStart += $angle;
			}
			$i++;
		}
		if ($angleEnd != 360) {
			$this->Sector($XDiag, $YDiag, $radius, $angleStart - $angle, 360);
		}

//Legends
		$this->SetFont(self::FONT_COURIER, '', 10);
		$x1 = $XPage + 2 * $radius + 4 * $margin;
		$x2 = $x1 + $hLegend + $margin;
		$y1 = $YDiag - $radius + (2 * $radius - $this->NbVal * ($hLegend + $margin)) / 2;
		for ($i = 0; $i < $this->NbVal; $i++) {
			$this->SetFillColor($colors[$i][0], $colors[$i][1], $colors[$i][2]);
			$this->Rect($x1, $y1, $hLegend, $hLegend, 'DF');
			$this->SetXY($x2, $y1);
			$this->Cell(0, $hLegend, $this->legends[$i]);
			$y1 += $hLegend + $margin;
		}
	}

	function BarDiagram($w, $h, $data, $format, $color = null, $maxVal = 0, $nbDiv = 4) {
		$this->SetFont(self::FONT_COURIER, '', 10);
		$this->SetLegends($data, $format);

		$XPage = $this->GetX();
		$YPage = $this->GetY();
		$margin = 2;
		$YDiag = $YPage + $margin;
		$hDiag = floor($h - $margin * 2);
		$XDiag = $XPage + $margin * 2 + $this->wLegend;
		$lDiag = floor($w - $margin * 3 - $this->wLegend);
		if ($color == null)
			$color = array(155, 155, 155);
		if ($maxVal == 0) {
			$maxVal = max($data);
		}
		$valIndRepere = ceil($maxVal / $nbDiv);
		$maxVal = $valIndRepere * $nbDiv;
		$lRepere = floor($lDiag / $nbDiv);
		$lDiag = $lRepere * $nbDiv;
		$unit = $lDiag / $maxVal;
		$hBar = floor($hDiag / ($this->NbVal + 1));
		$hDiag = $hBar * ($this->NbVal + 1);
		$eBaton = floor($hBar * 80 / 100);

		$this->SetLineWidth(0.2);
		$this->Rect($XDiag, $YDiag, $lDiag, $hDiag);

		$this->SetFont(self::FONT_COURIER, '', 10);
		$this->SetFillColor($color[0], $color[1], $color[2]);
		$i = 0;
		foreach ($data as $val) {
//Bar
			$xval = $XDiag;
			$lval = (int) ($val * $unit);
			$yval = $YDiag + ($i + 1) * $hBar - $eBaton / 2;
			$hval = $eBaton;
			$this->Rect($xval, $yval, $lval, $hval, 'DF');
//Legend
			$this->SetXY(0, $yval);
			$this->Cell($xval - $margin, $hval, $this->legends[$i], 0, 0, 'R');
			$i++;
		}

//Scales
		for ($i = 0; $i <= $nbDiv; $i++) {
			$xpos = $XDiag + $lRepere * $i;
			$this->Line($xpos, $YDiag, $xpos, $YDiag + $hDiag);
			$val = $i * $valIndRepere;
			$xpos = $XDiag + $lRepere * $i - $this->GetStringWidth($val) / 2;
			$ypos = $YDiag + $hDiag - $margin;
			$this->Text($xpos, $ypos, $val);
		}
	}

	function SetLegends($data, $format) {
		$this->legends = array();
		$this->wLegend = 0;
		$this->sum = array_sum($data);
		$this->NbVal = count($data);
		foreach ($data as $l => $val) {
			if ($this->sum == 0) {
				$p = "0%";
			} else {
				$p = sprintf('%.2f', $val / $this->sum * 100) . '%';
			}
			$legend = str_replace(array('%l', '%v', '%p'), array($l, $val, $p), $format);
			$this->legends[] = $legend;
			$this->wLegend = max($this->GetStringWidth($legend), $this->wLegend);
		}
	}

	var $B;
	var $I;
	var $U;
	var $HREF;

	function WriteHTML($html, $h = 5, $align = '') {
		$this->B = 0;
		$this->I = 0;
		$this->U = 0;
		$this->HREF = '';

		// HTML parser
		$html = str_replace("\n", ' ', $html);
		$a = preg_split('/<(.*)>/U', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
		foreach ($a as $i => $e) {
			if ($i % 2 == 0) {
				// Text
				if ($this->HREF)
					$this->PutLink($this->HREF, $e);
				else
					$this->Write($h, $e, '', $align);
			} else {
				// Tag
				if ($e[0] == '/') {
					$this->CloseTag(strtoupper(substr($e, 1)));
				} else {
					// Extract attributes
					$a2 = explode(' ', $e);
					$tag = strtoupper(array_shift($a2));
					$attr = array();
					foreach ($a2 as $v) {
						if (preg_match('/([^=]*)=["\']?([^"\']*)/', $v, $a3))
							$attr[strtoupper($a3[1])] = $a3[2];
					}
					$this->OpenTag($tag, $attr, $h);
				}
			}
		}
	}

	function OpenTag($tag, $attr, $h = 5) {
		// Opening tag
		if ($tag == 'B' || $tag == 'I' || $tag == 'U')
			$this->SetStyleHTML($tag, true);
		if ($tag == 'A')
			$this->HREF = $attr['HREF'];
		if ($tag == 'BR')
			$this->Ln($h);
	}

	function CloseTag($tag) {
		// Closing tag
		if ($tag == 'B' || $tag == 'I' || $tag == 'U')
			$this->SetStyleHTML($tag, false);
		if ($tag == 'A')
			$this->HREF = '';
	}

	function SetStyleHTML($tag, $enable) {
		// Modify style and select corresponding font
		$this->$tag += ($enable ? 1 : -1);
		$style = '';
		foreach (array('B', 'I', 'U') as $s) {
			if ($this->$s > 0)
				$style .= $s;
		}
		$this->SetFont('', $style);
	}

	function PutLink($URL, $txt) {
		// Put a hyperlink
		$this->SetTextColor(0, 0, 255);
		$this->SetStyleHTML('U', true);
		$this->Write(5, $txt, $URL);
		$this->SetStyleHTML('U', false);
		$this->SetTextColor(0);
	}
}

if (isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] == 'contype') {
	header('Content-Type: application/pdf');
	exit;
}
?>