<?php
/*******************************************************************\
*            Gemeinschaft - asterisk cluster gemeinschaft
* 
* $Revision$
* 
* Copyright 2007, amooma GmbH, Bachstr. 126, 56566 Neuwied, Germany,
* http://www.amooma.de/
* Stefan Wintermeyer <stefan.wintermeyer@amooma.de>
* Philipp Kempgen <philipp.kempgen@amooma.de>
* Peter Kozak <peter.kozak@amooma.de>
* 
* This program is free software; you can redistribute it and/or
* modify it under the terms of the GNU General Public License
* as published by the Free Software Foundation; either version 2
* of the License, or (at your option) any later version.
* 
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
* 
* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
* MA 02110-1301, USA.
\*******************************************************************/
defined('GS_VALID') or die('No direct access.');
include_once( GS_DIR .'inc/find_executable.php' );


function gs_pci_cards_detect()
{
	$lspci = find_executable('lspci', array(
		'/usr/bin/',
		'/usr/sbin/',
		'/bin/',
		'/sbin/'
	));
	if (! $lspci) {
		gs_log(GS_LOG_WARNING, 'lspci not found.');
		return null;
	}
	
	$err=0; $out=array();
	@exec( $lspci.' -m -n 2>>/dev/null', $out, $err );
	
	$out = array(
		'09:00.0 "Class 0200" "14e4" "164c" -r12 "1028" "01b2"',
		'0c:00.0 "Class 0604" "11ab" "1111" "" ""',
		'0d:08.0 "Class 0780" "d161" "0220" -r02 "0004" "0000"',
		'0f:00.0 "Class 0604" "8086" "0329" -r09 "" ""',
		'00:00.0 "Class 0000" "e159" "0001" "b1d9" "0003"',
		'00:00.0 "Class 0204" "e159" "0001" "0000" "0000"',
	);
	
	
	if ($err !== 0) {
		gs_log(GS_LOG_WARNING, 'lspci failed.');
		return null;
	}
	
	$cards = array();
	
	$i=0;
	foreach ($out as $line) {
		preg_match_all('/"([^"]*)"/', $line, $m, PREG_PATTERN_ORDER);
		if (count($m[1]) < 5) continue;
		
		$c = array();
		
		$c['class'] = strToLower($m[1][0]);
		$m2 = array();
		if (preg_match('/\b([0-9a-z]{4})\b/S', $class, $m2))
			$c['class'] = $m2[1];
		
		$c['vendorid'] = strToLower($m[1][1]);
		$m2 = array();
		if (preg_match('/\b([0-9a-z]{4})\b/S', $vendorid, $m2))
			$c['vendorid'] = $m2[1];
		
		$c['devid'] = strToLower($m[1][2]);
		$m2 = array();
		if (preg_match('/\b([0-9a-z]{4})\b/S', $devid, $m2))
			$c['devid'] = $m2[1];
		
		$c['subvendorid'] = strToLower($m[1][3]);
		$m2 = array();
		if (preg_match('/\b([0-9a-z]{4})\b/S', $subvendorid, $m2))
			$c['subvendorid'] = $m2[1];
		
		$c['subdevid'] = strToLower($m[1][4]);
		$m2 = array();
		if (preg_match('/\b([0-9a-z]{4})\b/S', $subdevid, $m2))
			$c['subdevid'] = $m2[1];
		
		$c['revision'] = '';
		$m2 = array();
		if (preg_match('/\s+-r([0-9a-z]{2})\b/S', $line, $m2))
			$c['revision'] = $m2[1];
		
		
		$c['vendor'] = '';
		$c['descr' ] = '';
		$c['known' ] = false;
		
		switch ($c['vendorid']) {
		
		case 'd161':  # Digium
			$c['vendor'] = 'Digium';
			switch ($c['devid']) {
				case '0120': $c['descr'] = 'TE120p (1-port PRI, 3rd gen., PCIe 3.3v)'; break;
				case '0205': $c['descr'] = 'TE205p/TE207p (2-port PRI, 3rd gen.)'; break;
				case '0210': $c['descr'] = 'TE210p/TE212p (2-port PRI, 3rd gen.)'; break;
				case '0220': $c['descr'] = 'TE220/TE220b (2-port PRI, 3rd gen., PCIe 3.3v)'; break;
				case '0405': $c['descr'] = 'TE405p/TE407p (4-port PRI, 3rd gen., 5.0v)'; break;
				case '0406': $c['descr'] = 'TE406p (4-port PRI, 3rd gen., 5.0v)'; break;
				case '0410': $c['descr'] = 'TE410p/TE412p (4-port PRI, 3rd gen., 3.3v)'; break;
				case '0411': $c['descr'] = 'TE411p (4-port PRI, 3rd gen., 3.3v)'; break;
				case '0420': $c['descr'] = 'TE420/TE420b (4-port PRI, 3rd gen., PCIe 3.3v)'; break;
				case '2400': $c['descr'] = 'TDM2400 (analog)'; break;
				case 'b410': $c['descr'] = 'B410p (4-port BRI)'; break;
			}
			break;
		
		case 'e159':  # Tiger Jet Network
			$c['vendor'] = 'Tiger Jet';
			$c['known' ] = true;
			switch ($c['devid']) {
				case '0001':
					if     ($c['subvendorid'] === '0059' && $c['subdevid'] === '0001')
						$c['descr'] = '3XX (128k ISDN-S/T)';
					elseif ($c['subvendorid'] === '0059' && $c['subdevid'] === '0003')
						$c['descr'] = '3XX (128k ISDN-U)';
					elseif ($c['subvendorid'] === '00a7' && $c['subdevid'] === '0001')
						$c['descr'] = 'Teles S0 ISDN';
					elseif ($c['subvendorid'] === '8086' && $c['subdevid'] === '0003')
						$c['descr'] = 'Digium X100p/X101p (analog FXO)';
					elseif ($c['subvendorid'] === 'b1d9' && $c['subdevid'] === '0003')
						$c['descr'] = 'Digium TDM400p/A400p (4x analog FXO/FXS)';
					else
						$c['descr'] = '3XX (modem/ISDN)';
					break;
				case '0002': $c['descr'] = '100APC (ISDN)'; break;
			}
			break;
		
		case '10ee':  # Xilinx
			$c['vendor'] = 'Xilinx';
			switch ($c['devid']) {
				case '0205': $c['descr'] = 'Digium TE205p (2-port PRI, 1st/2nd gen.)'; break;
				case '0210': $c['descr'] = 'Digium TE210p (2-port PRI, 1st/2nd gen.)'; break;
				case '0314': $c['descr'] = 'Digium TE405p/TE410p (4-port PRI, 1st gen.)'; break;
				case '0405': $c['descr'] = 'Digium TE405p (4-port PRI, 2nd gen.)'; break;
				case '0410': $c['descr'] = 'Digium TE410p (4-port PRI, 2nd gen.)'; break;
			}
			break;
		
		case '1057':  # Motorola
			$c['vendor'] = 'Motorola';
			switch ($c['devid']) {
				case '5608': $c['descr'] = 'Digium X100p (analog FXO)'; break;
			}
			break;
		
		case '1397':  # Cologne Chip Designs
			$c['vendor'] = 'Cologne';
			switch ($c['devid']) {
				case '2bd0': $c['descr'] = 'HFC-S 2BDS0 (BRI)'; break;
				case '8b4d': $c['descr'] = 'HFC-4S 8B4D4S0 (BRI, 4 S/T)'; break;
				case '0b4d': $c['descr'] = 'HFC-8S 16B8D8S0 (BRI, 8 S/T)'; break;
				case '08b4': $c['descr'] = 'HFC-4S (4-port BRI)'; break;
				case '16b8': $c['descr'] = 'HFC-8S (8-port BRI)'; break;
				case '30b1': $c['descr'] = 'HFC-E1 (PRI)'; break;
				case 'f001': $c['descr'] = 'HFC-4GSM (GSM)'; break;
			}
			break;
		
		default:
			if     ($c['class'] === '0204') {
				$c['descr'] = 'unknown ISDN controller';
			}
			elseif ($c['class'] === '0780') {
				$c['descr'] = 'unknown communication controller';
			}
		
		}
		
		if ($c['vendor'] != '' && $c['descr'] != '') {
			$c['known'] = true;
		}
		$cards[] = $c;
	}
	
	return $cards;
}


?>