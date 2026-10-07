/**
 * ======================================================================
 *  AGENDA-TIJDLIJN (iCal)  -  WPCode PHP-snippet, geen plugin
 * ======================================================================
 *  Versie    : 1.1.0  (2026-10-06)
 *  Shortcode : [agenda_tijdlijn]
 *              optioneel: [agenda_tijdlijn aankomend="3" voorbij="0"]
 *  Plakken   : WPCode > Snippet toevoegen > PHP-snippet > "Overal uitvoeren"
 *              (plak dit zonder openingstag <?php)
 *
 *  Leest een Google Agenda via de geheime iCal-link en toont een
 *  verticale tijdlijn: aankomend bovenaan, daarna voorbije events.
 *  Kleuren komen uit het thema (CSS-variabelen), nooit vaste hex-codes.
 *
 *  CHANGELOG
 *  1.1.0  Knop "Feed nu verversen" voor beheerders (onder de tijdlijn): haalt de
 *         feed opnieuw op, leegt de paginacache van die pagina (xSpeed, LiteSpeed
 *         Cache, WP Rocket, W3 Total Cache, SiteGround, WP Super Cache) en toont
 *         een korte uitslag. Eigen koppeling mogelijk via de actie bgtl_cache_legen.
 *         Vervangt de ?bgtl_ververs=1-truc (die deed niets meer).
 *  1.0.0  Eerste versie. Aankomend/voorbij automatisch uit de datum,
 *         transient + laatst bewaarde versie + foutpauze, beperkte HTML
 *         in beschrijvingen, link-regel in beschrijving, CSS in de snippet.
 *         Niet ondersteund: herhalende events (RRULE), foto's.
 *
 *  PER KLANT AANPASSEN: alleen het blok INSTELLINGEN hieronder.
 * ======================================================================
 */

/* ======================= INSTELLINGEN (per klant) ======================= */
if ( ! function_exists( 'bgtl_instellingen' ) ) {
	function bgtl_instellingen() {
		return array(
			// --- Bron ---------------------------------------------------
			// Geheime iCal-adres van de Google Agenda (https://calendar.google.com/calendar/ical/.../basic.ics)
			'feed_url'             => '',

			// --- Aantallen ----------------------------------------------
			'max_aankomend'        => 10,   // aantal aankomende events
			'max_voorbij'          => 5,    // aantal voorbije events (0 = geen)
			'voorbij_dagen'        => 365,  // voorbije events ouder dan dit aantal dagen worden niet getoond

			// --- Techniek -----------------------------------------------
			'cache_minuten'        => 60,   // hoe lang de feed bewaard blijft (min. 5)
			'tijdzone'             => 'Europe/Amsterdam',
			'css_voorvoegsel'      => 'k9tl', // letters/cijfers/streepje, begint met een letter
			'kop_niveau'           => 3,    // 2 t/m 6: h-niveau van de event-titels

			// --- Teksten ------------------------------------------------
			'tekst_geen_events'    => 'Er staan op dit moment geen events op de agenda.',
			'tekst_fout'           => 'De agenda is op dit moment niet beschikbaar. Probeer het later nog eens.',
			'label_aankomend'      => 'Aankomend',
			'label_nu'             => 'Nu bezig',
			'label_voorbij'        => 'Voorbij',
			'tekst_hele_dag'       => 'Hele dag',
			'link_tekst_aankomend' => 'Meer informatie',
			'link_tekst_voorbij'   => 'Bekijk terug',

			// --- Kleuren (CSS-variabelen van het thema) -------------------
			// Blocksy: --theme-palette-color-1 t/m 8. Elementor-globaal: var(--e-global-color-accent)
			'kleur_hoofd'          => 'var(--theme-palette-color-1)', // titels, links, lijn, marker voorbij
			'kleur_tekst'          => 'var(--theme-palette-color-3)', // lopende tekst
			'kleur_accent'         => 'var(--theme-palette-color-4)', // marker aankomend, pijltje
			'kleur_accent_tekst'   => 'var(--theme-palette-color-5)', // label "Aankomend": donkerder accent (leesbaar)
			'kleur_zand'           => 'var(--theme-palette-color-6)', // zachte kaartachtergrond aankomend
			'kleur_vlak'           => 'var(--theme-palette-color-7)', // achtergrond van de pagina (rand om marker)
		);
	}
}
/* ===================== EINDE INSTELLINGEN ===================== */


/* ------------------------------------------------------------------
 *  Hulpfuncties (niet aanpassen)
 * ------------------------------------------------------------------ */

if ( ! function_exists( 'bgtl_versie' ) ) {
	function bgtl_versie() {
		return '1.1.0';
	}
}

/** Maakt van de ruwe instellingen een veilige, complete set. */
if ( ! function_exists( 'bgtl_normaliseer' ) ) {
	function bgtl_normaliseer( $i ) {
		$d = array(
			'feed_url' => '', 'max_aankomend' => 10, 'max_voorbij' => 5, 'voorbij_dagen' => 365,
			'cache_minuten' => 60, 'tijdzone' => 'Europe/Amsterdam', 'css_voorvoegsel' => 'k9tl', 'kop_niveau' => 3,
			'tekst_geen_events' => 'Er staan op dit moment geen events op de agenda.',
			'tekst_fout' => 'De agenda is op dit moment niet beschikbaar. Probeer het later nog eens.',
			'label_aankomend' => 'Aankomend', 'label_nu' => 'Nu bezig', 'label_voorbij' => 'Voorbij',
			'tekst_hele_dag' => 'Hele dag', 'link_tekst_aankomend' => 'Meer informatie', 'link_tekst_voorbij' => 'Bekijk terug',
			'kleur_hoofd' => 'var(--theme-palette-color-1)', 'kleur_tekst' => 'var(--theme-palette-color-3)',
			'kleur_accent' => 'var(--theme-palette-color-4)', 'kleur_accent_tekst' => 'var(--theme-palette-color-5)',
			'kleur_zand' => 'var(--theme-palette-color-6)', 'kleur_vlak' => 'var(--theme-palette-color-7)',
		);
		$i = array_merge( $d, is_array( $i ) ? $i : array() );

		$url = trim( (string) $i['feed_url'] );
		if ( 0 === stripos( $url, 'webcal://' ) ) {
			$url = 'https://' . substr( $url, 9 );
		}
		$i['feed_url'] = ( 0 === stripos( $url, 'https://' ) ) ? $url : '';

		$i['max_aankomend']  = max( 0, min( 100, (int) $i['max_aankomend'] ) );
		$i['max_voorbij']    = max( 0, min( 100, (int) $i['max_voorbij'] ) );
		$i['voorbij_dagen']  = max( 0, min( 3650, (int) $i['voorbij_dagen'] ) );
		$i['cache_minuten']  = max( 5, min( 1440, (int) $i['cache_minuten'] ) );
		$i['kop_niveau']     = max( 2, min( 6, (int) $i['kop_niveau'] ) );

		if ( ! in_array( $i['tijdzone'], timezone_identifiers_list(), true ) ) {
			$i['tijdzone'] = 'Europe/Amsterdam';
		}

		$p = strtolower( preg_replace( '/[^a-zA-Z0-9-]/', '', (string) $i['css_voorvoegsel'] ) );
		$i['css_voorvoegsel'] = preg_match( '/^[a-z]/', $p ) ? $p : $d['css_voorvoegsel'];

		foreach ( array( 'kleur_hoofd', 'kleur_tekst', 'kleur_accent', 'kleur_accent_tekst', 'kleur_zand', 'kleur_vlak' ) as $k ) {
			$w = trim( (string) $i[ $k ] );
			// Alleen letters, cijfers en tekens die in var(--naam) / #hex / rgb() voorkomen.
			$i[ $k ] = preg_match( '/^[a-zA-Z0-9#(),.%\s_-]{1,80}$/', $w ) ? $w : $d[ $k ];
		}

		foreach ( array( 'tekst_geen_events', 'tekst_fout', 'label_aankomend', 'label_nu', 'label_voorbij', 'tekst_hele_dag', 'link_tekst_aankomend', 'link_tekst_voorbij' ) as $k ) {
			$i[ $k ] = (string) $i[ $k ];
		}
		return $i;
	}
}

/* ------------------------------------------------------------------
 *  iCal lezen
 * ------------------------------------------------------------------ */

/** Zet \n, \, \; en \\ uit iCal-tekst om. */
if ( ! function_exists( 'bgtl_ical_tekst' ) ) {
	function bgtl_ical_tekst( $s ) {
		return preg_replace_callback(
			'/\\\\(.)/s',
			function ( $m ) {
				return ( 'n' === $m[1] || 'N' === $m[1] ) ? "\n" : $m[1];
			},
			(string) $s
		);
	}
}

/** Ontvouwt regels (een regel die met spatie/tab begint hoort bij de vorige). */
if ( ! function_exists( 'bgtl_ical_regels' ) ) {
	function bgtl_ical_regels( $raw ) {
		$raw = (string) $raw;
		if ( 0 === strncmp( $raw, "\xEF\xBB\xBF", 3 ) ) {
			$raw = substr( $raw, 3 );
		}
		$raw = str_replace( array( "\r\n", "\r" ), "\n", $raw );
		$raw = preg_replace( '/\n[ \t]/', '', $raw );
		return explode( "\n", $raw );
	}
}

/** Splitst "NAAM;PARAM=x:waarde" in array( NAAM, array(PARAM=>x), waarde ). Dubbele aanhalingstekens worden gerespecteerd. */
if ( ! function_exists( 'bgtl_ical_regel' ) ) {
	function bgtl_ical_regel( $regel ) {
		$len = strlen( $regel );
		$inq = false;
		$pos = -1;
		for ( $k = 0; $k < $len; $k++ ) {
			$c = $regel[ $k ];
			if ( '"' === $c ) {
				$inq = ! $inq;
			} elseif ( ':' === $c && ! $inq ) {
				$pos = $k;
				break;
			}
		}
		if ( $pos < 1 ) {
			return null;
		}
		$kop    = substr( $regel, 0, $pos );
		$waarde = substr( $regel, $pos + 1 );

		$delen = array();
		$buf   = '';
		$inq   = false;
		$klen  = strlen( $kop );
		for ( $k = 0; $k < $klen; $k++ ) {
			$c = $kop[ $k ];
			if ( '"' === $c ) {
				$inq  = ! $inq;
				$buf .= $c;
			} elseif ( ';' === $c && ! $inq ) {
				$delen[] = $buf;
				$buf     = '';
			} else {
				$buf .= $c;
			}
		}
		$delen[] = $buf;

		$naam = strtoupper( trim( (string) array_shift( $delen ) ) );
		$par  = array();
		foreach ( $delen as $d ) {
			$eq = strpos( $d, '=' );
			if ( false === $eq ) {
				continue;
			}
			$par[ strtoupper( trim( substr( $d, 0, $eq ) ) ) ] = trim( substr( $d, $eq + 1 ), " \t\"" );
		}
		return array( $naam, $par, $waarde );
	}
}

/** Datum/tijd uit iCal naar array( timestamp, hele_dag ) of null. */
if ( ! function_exists( 'bgtl_ical_tijd' ) ) {
	function bgtl_ical_tijd( $waarde, $par, $tz ) {
		$waarde = trim( (string) $waarde );
		try {
			if ( preg_match( '/^\d{8}$/', $waarde ) ) {
				$d = DateTimeImmutable::createFromFormat( '!Ymd', $waarde, $tz );
				return $d ? array( $d->getTimestamp(), true ) : null;
			}
			if ( preg_match( '/^(\d{8}T\d{6})(Z?)$/', $waarde, $m ) ) {
				if ( 'Z' === $m[2] ) {
					$zone = new DateTimeZone( 'UTC' );
				} elseif ( ! empty( $par['TZID'] ) ) {
					try {
						$zone = new DateTimeZone( $par['TZID'] );
					} catch ( Exception $e ) {
						$zone = $tz; // onbekende zonenaam: val terug op de instelling
					}
				} else {
					$zone = $tz; // "zwevende" tijd
				}
				$d = DateTimeImmutable::createFromFormat( 'Ymd\THis', $m[1], $zone );
				return $d ? array( $d->getTimestamp(), false ) : null;
			}
		} catch ( Exception $e ) {
			return null;
		}
		return null;
	}
}

/**
 * Eén VEVENT (eigenschappen) naar een genormaliseerd event.
 * Geeft een array terug, of een tekst met de reden van overslaan.
 */
if ( ! function_exists( 'bgtl_ical_event' ) ) {
	function bgtl_ical_event( $p, $tz ) {
		if ( isset( $p['STATUS'] ) && 'CANCELLED' === strtoupper( trim( $p['STATUS'][1] ) ) ) {
			return 'geannuleerd';
		}
		if ( isset( $p['RRULE'] ) || isset( $p['RECURRENCE-ID'] ) ) {
			return 'herhalend';
		}
		if ( ! isset( $p['DTSTART'] ) ) {
			return 'ongeldig';
		}
		$s = bgtl_ical_tijd( $p['DTSTART'][1], $p['DTSTART'][0], $tz );
		if ( ! $s ) {
			return 'ongeldig';
		}
		$titel = isset( $p['SUMMARY'] ) ? trim( bgtl_ical_tekst( $p['SUMMARY'][1] ) ) : '';
		if ( '' === $titel ) {
			return 'zonder_titel';
		}

		$start    = $s[0];
		$hele_dag = $s[1];
		$e        = null;
		if ( isset( $p['DTEND'] ) ) {
			$e = bgtl_ical_tijd( $p['DTEND'][1], $p['DTEND'][0], $tz );
		} elseif ( isset( $p['DURATION'] ) ) {
			try {
				$dt = ( new DateTimeImmutable( '@' . $start ) )->setTimezone( $tz )->add( new DateInterval( trim( $p['DURATION'][1] ) ) );
				$e  = array( $dt->getTimestamp(), $hele_dag );
			} catch ( Exception $ex ) {
				$e = null;
			}
		}

		$eind_bekend = false;
		if ( $hele_dag ) {
			// Google levert de einddatum van hele-dag-events exclusief (de dag erna).
			$eind = ( $e && $e[0] > $start ) ? $e[0] : ( new DateTimeImmutable( '@' . $start ) )->setTimezone( $tz )->modify( '+1 day' )->getTimestamp();
			$eind_bekend = true;
		} elseif ( $e && $e[0] > $start ) {
			$eind        = $e[0];
			$eind_bekend = true;
		} else {
			// Geen eindtijd: het event telt als bezig tot het einde van die dag.
			$eind = ( new DateTimeImmutable( '@' . $start ) )->setTimezone( $tz )->modify( 'midnight +1 day' )->getTimestamp();
		}

		$beschr = isset( $p['DESCRIPTION'] ) ? trim( bgtl_ical_tekst( $p['DESCRIPTION'][1] ) ) : '';
		$locatie = isset( $p['LOCATION'] ) ? trim( preg_replace( '/\s+/', ' ', bgtl_ical_tekst( $p['LOCATION'][1] ) ) ) : '';

		return array(
			'titel'       => mb_substr( preg_replace( '/\s+/', ' ', $titel ), 0, 200 ),
			'beschrijving' => mb_substr( $beschr, 0, 3000 ),
			'locatie'     => mb_substr( $locatie, 0, 200 ),
			'start'       => $start,
			'eind'        => $eind,
			'eind_bekend' => $eind_bekend,
			'hele_dag'    => $hele_dag,
		);
	}
}

/** Hele iCal-tekst naar events. Bewaart alleen wat voor de tijdlijn nodig kan zijn. */
if ( ! function_exists( 'bgtl_ical_parse' ) ) {
	function bgtl_ical_parse( $raw, $i, $nu ) {
		$tz     = new DateTimeZone( $i['tijdzone'] );
		$grens  = $nu - ( $i['voorbij_dagen'] + 1 ) * DAY_IN_SECONDS;
		$events = array();
		$over   = array( 'herhalend' => 0, 'zonder_titel' => 0, 'ongeldig' => 0, 'afgekapt' => 0 );
		$p      = null;
		$aantal = 0;

		foreach ( bgtl_ical_regels( $raw ) as $regel ) {
			if ( '' === $regel ) {
				continue;
			}
			$u = strtoupper( rtrim( $regel ) );
			if ( 'BEGIN:VEVENT' === $u ) {
				$p = array();
				if ( ++$aantal > 5000 ) {
					$over['afgekapt'] = 1; // beveiliging tegen gigantische feeds
					break;
				}
				continue;
			}
			if ( 'END:VEVENT' === $u ) {
				if ( is_array( $p ) ) {
					$ev = bgtl_ical_event( $p, $tz );
					if ( is_array( $ev ) ) {
						if ( $ev['eind'] >= $grens ) {
							$events[] = $ev;
						}
					} elseif ( isset( $over[ $ev ] ) ) {
						++$over[ $ev ];
					}
				}
				$p = null;
				continue;
			}
			if ( null === $p ) {
				continue;
			}
			$r = bgtl_ical_regel( $regel );
			if ( $r && in_array( $r[0], array( 'SUMMARY', 'DESCRIPTION', 'LOCATION', 'DTSTART', 'DTEND', 'DURATION', 'STATUS', 'RRULE', 'RECURRENCE-ID' ), true ) ) {
				$p[ $r[0] ] = array( $r[1], $r[2] );
			}
		}

		// Houd het klein: maximaal 200 toekomstige en 200 voorbije events bewaren.
		$komend  = array();
		$voorbij = array();
		foreach ( $events as $ev ) {
			if ( $ev['eind'] > $nu ) {
				$komend[] = $ev;
			} else {
				$voorbij[] = $ev;
			}
		}
		usort( $komend, function ( $a, $b ) { return $a['start'] <=> $b['start']; } );
		usort( $voorbij, function ( $a, $b ) { return $b['eind'] <=> $a['eind']; } );

		return array(
			'events'       => array_merge( array_slice( $komend, 0, 200 ), array_slice( $voorbij, 0, 200 ) ),
			'overgeslagen' => $over,
			'opgehaald'    => $nu,
		);
	}
}

/* ------------------------------------------------------------------
 *  Ophalen en bewaren (transient + laatst bewaarde versie + foutpauze)
 * ------------------------------------------------------------------ */
if ( ! function_exists( 'bgtl_haal_events' ) ) {
	function bgtl_haal_events( $i, $nu, $forceer = false ) {
		$leeg = array( 'events' => array(), 'overgeslagen' => array(), 'opgehaald' => 0, 'bron' => 'geen', 'fout' => '' );

		if ( '' === $i['feed_url'] ) {
			$leeg['fout'] = 'Geen (geldige, https) feed-URL ingesteld in het blok INSTELLINGEN.';
			return $leeg;
		}

		$url_hash = md5( $i['feed_url'] );
		$sleutel  = 'bgtl_v1_' . md5( $i['feed_url'] . '|' . $i['voorbij_dagen'] . '|' . $i['tijdzone'] );
		$laatste  = 'bgtl_laatste_' . $url_hash;
		$pauze    = 'bgtl_pauze_' . $url_hash;

		if ( ! $forceer ) {
			$vers = get_transient( $sleutel );
			if ( is_array( $vers ) && isset( $vers['events'] ) ) {
				$vers['bron'] = 'cache';
				$vers['fout'] = '';
				return $vers;
			}
		}

		$fout = '';
		$pauzemelding = ( ! $forceer ) ? get_transient( $pauze ) : false;
		if ( is_string( $pauzemelding ) && '' !== $pauzemelding ) {
			$fout = $pauzemelding; // recent mislukt: Google niet nogmaals bevragen
		} else {
			$r = wp_safe_remote_get(
				$i['feed_url'],
				array(
					'timeout'             => 6,
					'redirection'         => 3,
					'limit_response_size' => 5 * MB_IN_BYTES,
					'user-agent'          => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url( '/' ),
					'headers'             => array( 'Accept' => 'text/calendar, text/plain;q=0.8, */*;q=0.5' ),
				)
			);
			if ( is_wp_error( $r ) ) {
				$fout = 'Feed niet bereikbaar: ' . $r->get_error_message();
			} elseif ( 200 !== (int) wp_remote_retrieve_response_code( $r ) ) {
				$fout = 'Feed gaf HTTP ' . (int) wp_remote_retrieve_response_code( $r ) . ' terug.';
			} else {
				$body = (string) wp_remote_retrieve_body( $r );
				if ( false === stripos( $body, 'BEGIN:VCALENDAR' ) ) {
					$fout = 'Het antwoord is geen iCal-feed (controleer de URL).';
				} elseif ( false === stripos( $body, 'END:VCALENDAR' ) ) {
					$fout = 'De feed is onvolledig of groter dan 5 MB.';
				} else {
					$data = bgtl_ical_parse( $body, $i, $nu );
					set_transient( $sleutel, $data, $i['cache_minuten'] * MINUTE_IN_SECONDS );
					update_option( $laatste, $data, false );
					delete_transient( $pauze );
					$data['bron'] = 'vers';
					$data['fout'] = '';
					return $data;
				}
			}
			if ( '' !== $fout ) {
				set_transient( $pauze, $fout, 5 * MINUTE_IN_SECONDS ); // 5 minuten rust na een mislukte poging
			}
		}

		$bewaard = get_option( $laatste );
		if ( is_array( $bewaard ) && isset( $bewaard['events'] ) ) {
			$bewaard['bron'] = 'bewaard';
			$bewaard['fout'] = $fout;
			return $bewaard;
		}
		$leeg['fout'] = $fout;
		return $leeg;
	}
}

/* ------------------------------------------------------------------
 *  Verversknop (alleen beheerders)
 * ------------------------------------------------------------------ */

/** Adres van de pagina waarop de tijdlijn staat (zonder zoekparameters). */
if ( ! function_exists( 'bgtl_basis_url' ) ) {
	function bgtl_basis_url() {
		$id = is_singular() ? get_queried_object_id() : 0;
		$u  = $id ? get_permalink( $id ) : '';
		return $u ? $u : home_url( '/' );
	}
}

/** Leegt de paginacache voor één URL bij de bekende cache-plugins. Geeft de namen terug van wat is aangeroepen. */
if ( ! function_exists( 'bgtl_leeg_paginacache' ) ) {
	function bgtl_leeg_paginacache( $url ) {
		$gedaan = array();
		try {
			if ( defined( 'LSCWP_V' ) || has_action( 'litespeed_purge_url' ) ) {
				do_action( 'litespeed_purge_url', $url );
				$gedaan[] = 'LiteSpeed Cache';
			}
			if ( class_exists( 'XSpeed\\Cache' ) && is_callable( array( 'XSpeed\\Cache', 'purge_url' ) ) ) {
				\XSpeed\Cache::purge_url( $url, 'bgtl' );
				$gedaan[] = 'xSpeed Cache';
			}
			if ( function_exists( 'rocket_clean_files' ) ) {
				rocket_clean_files( $url );
				$gedaan[] = 'WP Rocket';
			}
			if ( function_exists( 'w3tc_flush_url' ) ) {
				w3tc_flush_url( $url );
				$gedaan[] = 'W3 Total Cache';
			}
			if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
				sg_cachepress_purge_cache( $url );
				$gedaan[] = 'SiteGround Optimizer';
			}
			if ( function_exists( 'wp_cache_clear_cache' ) ) {
				wp_cache_clear_cache();
				$gedaan[] = 'WP Super Cache (alles)';
			}
			do_action( 'bgtl_cache_legen', $url ); // eigen koppeling per klantsite
		} catch ( \Throwable $e ) {
			$gedaan[] = 'fout bij het legen van een cache';
		}
		return $gedaan;
	}
}

/** Verwerkt een klik op de knop: feed verversen, paginacache legen, terug naar de pagina met een uitslag. */
if ( ! function_exists( 'bgtl_verwerk_verversen' ) ) {
	function bgtl_verwerk_verversen() {
		if ( ! isset( $_GET['bgtl_ververs'] ) || ! current_user_can( 'manage_options' ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		$basis = bgtl_basis_url();
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'bgtl_ververs' ) ) {
			wp_safe_redirect( $basis ); // verlopen of ongeldige link: er is niets gedaan
			exit;
		}
		$rapport = array( 'feed' => '', 'cache' => array() );
		try {
			$i    = bgtl_normaliseer( bgtl_instellingen() );
			$data = bgtl_haal_events( $i, time(), true );
			if ( 'vers' === $data['bron'] ) {
				$rapport['feed'] = 'Feed opnieuw opgehaald (' . count( $data['events'] ) . ' events gelezen).';
			} else {
				$rapport['feed'] = 'Ophalen mislukt: ' . $data['fout'] . ' De laatst bewaarde versie blijft zichtbaar.';
			}
			$rapport['cache'] = bgtl_leeg_paginacache( $basis );
		} catch ( \Throwable $e ) {
			$rapport['feed'] = 'Verversen is niet gelukt.';
		}
		set_transient( 'bgtl_rapport_' . get_current_user_id(), $rapport, 2 * MINUTE_IN_SECONDS );
		// Een uniek adres, zodat een paginacache de uitslag niet kan overslaan.
		wp_safe_redirect( add_query_arg( 'bgtl_klaar', time(), $basis ) );
		exit;
	}
}

/* ------------------------------------------------------------------
 *  Weergave
 * ------------------------------------------------------------------ */

/** Beschrijving naar veilige HTML plus optionele link-regel ("link: https://... | Knoptekst"). */
if ( ! function_exists( 'bgtl_beschrijving' ) ) {
	function bgtl_beschrijving( $raw ) {
		$t = str_replace( array( "\r\n", "\r" ), "\n", (string) $raw );
		$t = preg_replace( '~<\s*(script|style)\b[^>]*>.*?<\s*/\s*\1\s*>~is', '', $t ); // ook de inhoud van script/style weg
		// Blokgrenzen uit Google-HTML worden regeleinden; opsommingen worden regels met een bolletje.
		$t = preg_replace( '~<\s*li(\s[^>]*)?>~i', "\n\xE2\x80\xA2 ", $t );
		$t = preg_replace( '~<\s*/\s*li\s*>~i', '', $t );
		$t = preg_replace( '~<\s*br\s*/?>|<\s*/\s*(p|div|h[1-6]|ul|ol)\s*>|<\s*(p|div)(\s[^>]*)?>~i', "\n", $t );

		$toegestaan = array(
			'a'      => array( 'href' => true ),
			'strong' => array(),
			'b'      => array(),
			'em'     => array(),
			'i'      => array(),
		);

		$link   = '';
		$knop   = '';
		$alinea = array();
		$huidig = array();

		foreach ( explode( "\n", $t ) as $regel ) {
			$plat = trim( wp_strip_all_tags( $regel ) );
			if ( '' === $plat ) {
				if ( $huidig ) {
					$alinea[] = $huidig;
					$huidig   = array();
				}
				continue;
			}
			if ( '' === $link && preg_match( '~^link\s*:\s*(https?://\S+)\s*(?:\|\s*(.+))?$~iu', $plat, $m ) ) {
				$link = html_entity_decode( $m[1], ENT_QUOTES, 'UTF-8' );
				$knop = isset( $m[2] ) ? trim( $m[2] ) : '';
				continue;
			}
			$schoon = wp_kses( make_clickable( $regel ), $toegestaan, array( 'http', 'https' ) );
			$schoon = preg_replace( '~<a\s+href=~i', '<a rel="noopener noreferrer" href=', $schoon );
			// Links zonder http(s)-adres (bv. een weggefilterde javascript:- of mailto:-link) worden gewone tekst.
			$schoon = preg_replace_callback(
				'~<a rel="noopener noreferrer" href="([^"]*)">(.*?)</a>~is',
				function ( $m ) {
					return preg_match( '~^https?://~i', $m[1] ) ? $m[0] : $m[2];
				},
				$schoon
			);
			if ( '' !== trim( wp_strip_all_tags( $schoon ) ) ) {
				$huidig[] = $schoon;
			}
		}
		if ( $huidig ) {
			$alinea[] = $huidig;
		}

		$html = '';
		foreach ( $alinea as $regels ) {
			$html .= '<p>' . implode( '<br>', $regels ) . '</p>';
		}
		return array( 'html' => $html, 'link' => $link, 'knop' => $knop );
	}
}

/** Datum- en tijdtekst voor een event (via wp_date, in de ingestelde tijdzone). */
if ( ! function_exists( 'bgtl_datum' ) ) {
	function bgtl_datum( $ev, $i, $tz ) {
		$s  = $ev['start'];
		$e1 = max( $s, $ev['eind'] - 1 ); // laatste seconde van het event
		$d1 = wp_date( 'Ymd', $s, $tz );
		$d2 = wp_date( 'Ymd', $e1, $tz );

		if ( $d1 === $d2 ) {
			$datum = wp_date( 'D j M Y', $s, $tz );
			if ( $ev['hele_dag'] ) {
				$tijd = $i['tekst_hele_dag'];
			} elseif ( $ev['eind_bekend'] ) {
				$tijd = wp_date( 'H:i', $s, $tz ) . '–' . wp_date( 'H:i', $ev['eind'], $tz );
			} else {
				$tijd = wp_date( 'H:i', $s, $tz );
			}
		} else {
			$zelfde_jaar = wp_date( 'Y', $s, $tz ) === wp_date( 'Y', $e1, $tz );
			$datum = wp_date( $zelfde_jaar ? 'D j M' : 'D j M Y', $s, $tz ) . ' – ' . wp_date( 'D j M Y', $e1, $tz );
			$tijd  = $ev['hele_dag'] ? '' : wp_date( 'H:i', $s, $tz ) . ' – ' . wp_date( 'H:i', $ev['eind'], $tz );
		}
		$attr = $ev['hele_dag'] ? wp_date( 'Y-m-d', $s, $tz ) : wp_date( 'c', $s, $tz );
		return array( 'datum' => $datum, 'tijd' => $tijd, 'attr' => $attr );
	}
}

/** Kiest en sorteert wat getoond wordt: aankomend (oplopend), dan voorbij (nieuwste eerst). */
if ( ! function_exists( 'bgtl_selecteer' ) ) {
	function bgtl_selecteer( $events, $i, $nu ) {
		$komend  = array();
		$voorbij = array();
		$grens   = $nu - $i['voorbij_dagen'] * DAY_IN_SECONDS;
		foreach ( $events as $ev ) {
			if ( $ev['eind'] > $nu ) {
				$komend[] = $ev;
			} elseif ( $ev['eind'] >= $grens ) {
				$voorbij[] = $ev;
			}
		}
		usort( $komend, function ( $a, $b ) { return array( $a['start'], $a['titel'] ) <=> array( $b['start'], $b['titel'] ); } );
		usort( $voorbij, function ( $a, $b ) { return array( $b['eind'], $b['start'] ) <=> array( $a['eind'], $a['start'] ); } );
		return array_merge( array_slice( $komend, 0, $i['max_aankomend'] ), array_slice( $voorbij, 0, $i['max_voorbij'] ) );
	}
}

/** De HTML van de tijdlijn (zonder CSS). */
if ( ! function_exists( 'bgtl_html' ) ) {
	function bgtl_html( $data, $i, $nu, $beheer = false ) {
		$p      = $i['css_voorvoegsel'];
		$tz     = new DateTimeZone( $i['tijdzone'] );
		$items  = bgtl_selecteer( $data['events'], $i, $nu );
		$kop    = 'h' . $i['kop_niveau'];
		$uit    = '<div class="' . esc_attr( $p ) . '" data-versie="' . esc_attr( bgtl_versie() ) . '">';

		if ( ! $items ) {
			$geen_bron = ( 'geen' === $data['bron'] && '' !== $data['fout'] );
			$uit .= '<p class="' . $p . '__melding">' . esc_html( $geen_bron ? $i['tekst_fout'] : $i['tekst_geen_events'] ) . '</p>';
		} else {
			$uit .= '<ol class="' . $p . '__lijst">';
			foreach ( $items as $ev ) {
				$status = ( $ev['eind'] <= $nu ) ? 'voorbij' : ( ( $ev['start'] <= $nu ) ? 'nu' : 'aankomend' );
				$mod    = ( 'voorbij' === $status ) ? 'voorbij' : 'aankomend';
				$label  = $i[ 'label_' . $status ];
				$dt     = bgtl_datum( $ev, $i, $tz );
				$b      = bgtl_beschrijving( $ev['beschrijving'] );

				$uit .= '<li class="' . $p . '__item ' . $p . '__item--' . $mod . '"><article class="' . $p . '__kaart">';
				$uit .= '<p class="' . $p . '__meta"><span class="' . $p . '__status">' . esc_html( $label ) . '</span>';
				$uit .= '<time class="' . $p . '__datum" datetime="' . esc_attr( $dt['attr'] ) . '">' . esc_html( $dt['datum'] ) . '</time></p>';
				$uit .= '<' . $kop . ' class="' . $p . '__titel">' . esc_html( $ev['titel'] ) . '</' . $kop . '>';
				if ( '' !== $dt['tijd'] ) {
					$uit .= '<p class="' . $p . '__tijd">' . esc_html( $dt['tijd'] ) . '</p>';
				}
				if ( '' !== $ev['locatie'] ) {
					$uit .= '<p class="' . $p . '__locatie">' . esc_html( $ev['locatie'] ) . '</p>';
				}
				if ( '' !== $b['html'] ) {
					$uit .= '<div class="' . $p . '__tekst">' . $b['html'] . '</div>';
				}
				$url = ( '' !== $b['link'] ) ? esc_url( $b['link'], array( 'http', 'https' ) ) : '';
				if ( '' !== $url ) {
					$tekst = ( '' !== $b['knop'] ) ? $b['knop'] : ( 'voorbij' === $mod ? $i['link_tekst_voorbij'] : $i['link_tekst_aankomend'] );
					$uit .= '<a class="' . $p . '__link" href="' . $url . '" aria-label="' . esc_attr( $tekst . ': ' . $ev['titel'] ) . '">' . esc_html( $tekst ) . ' <span aria-hidden="true">→</span></a>';
				}
				$uit .= '</article></li>';
			}
			$uit .= '</ol>';
		}

		if ( $beheer ) {
			$noten = array();
			$bron  = array( 'vers' => 'zojuist opgehaald', 'cache' => 'uit de cache', 'bewaard' => 'laatst bewaarde versie (ophalen mislukt)', 'geen' => 'geen gegevens' );
			$noten[] = 'Bron: ' . ( isset( $bron[ $data['bron'] ] ) ? $bron[ $data['bron'] ] : $data['bron'] )
				. ( ! empty( $data['opgehaald'] ) ? ', opgehaald ' . wp_date( 'j M Y H:i', $data['opgehaald'], $tz ) : '' ) . '.';
			if ( '' !== $data['fout'] ) {
				$noten[] = 'Melding: ' . $data['fout'];
			}
			$o = isset( $data['overgeslagen'] ) ? $data['overgeslagen'] : array();
			if ( ! empty( $o['herhalend'] ) ) {
				$noten[] = (int) $o['herhalend'] . ' herhalende event(s) overgeslagen (niet ondersteund in versie 1).';
			}
			if ( ! empty( $o['zonder_titel'] ) ) {
				$noten[] = (int) $o['zonder_titel'] . ' event(s) zonder titel overgeslagen.';
			}
			if ( ! empty( $o['ongeldig'] ) ) {
				$noten[] = (int) $o['ongeldig'] . ' event(s) met een onleesbare datum overgeslagen.';
			}
			if ( ! empty( $o['afgekapt'] ) ) {
				$noten[] = 'De feed bevat meer dan 5000 events; de rest is genegeerd.';
			}
			$rapport = get_transient( 'bgtl_rapport_' . get_current_user_id() );
			if ( is_array( $rapport ) ) {
				delete_transient( 'bgtl_rapport_' . get_current_user_id() );
				$cache_tekst = $rapport['cache'] ? 'Paginacache geleegd bij: ' . implode( ', ', $rapport['cache'] ) . '.' : 'Geen bekende paginacache-plugin gevonden om te legen.';
				array_unshift( $noten, 'Verversen: ' . $rapport['feed'] . ' ' . $cache_tekst );
			}
			foreach ( $noten as $n ) {
				$uit .= '<p class="' . $p . '__melding">' . esc_html( $n ) . '</p>';
			}
			$knop_url = wp_nonce_url( add_query_arg( 'bgtl_ververs', '1', bgtl_basis_url() ), 'bgtl_ververs' );
			$uit .= '<p class="' . $p . '__melding">Alleen zichtbaar voor beheerders. <a class="' . $p . '__knop" href="' . esc_url( $knop_url ) . '">Feed nu verversen</a></p>';
		}

		return $uit . '</div>';
	}
}

/** De CSS, met het voorvoegsel en de kleuren uit de instellingen. */
if ( ! function_exists( 'bgtl_css' ) ) {
	function bgtl_css( $i ) {
		$css = <<<'BGTLCSS'
.{p}{--{p}-hoofd:{c_hoofd};--{p}-tekst:{c_tekst};--{p}-accent:{c_accent};--{p}-accent-tekst:{c_accent_tekst};--{p}-zand:{c_zand};--{p}-vlak:{c_vlak};
--{p}-tekst-2:var(--{p}-tekst);--{p}-tekst-3:var(--{p}-tekst);--{p}-titel-voorbij:var(--{p}-hoofd);--{p}-marker-voorbij:var(--{p}-hoofd);--{p}-ring:var(--{p}-hoofd);
--{p}-lijn-a:var(--{p}-accent);--{p}-lijn-b:var(--{p}-hoofd);--{p}-lijn-c:var(--{p}-hoofd);--{p}-streep:var(--{p}-hoofd);--{p}-rand:var(--{p}-hoofd);
--{p}-ring-accent:var(--{p}-accent);--{p}-halo:transparent;--{p}-kaart-a:transparent;--{p}-kaart-b:transparent;--{p}-kaart-c:transparent}
@supports (color:color-mix(in srgb,red 50%,transparent)){.{p}{
--{p}-tekst-2:color-mix(in srgb,var(--{p}-tekst) 78%,transparent);--{p}-tekst-3:color-mix(in srgb,var(--{p}-tekst) 72%,transparent);
--{p}-titel-voorbij:color-mix(in srgb,var(--{p}-hoofd) 78%,transparent);--{p}-marker-voorbij:color-mix(in srgb,var(--{p}-hoofd) 45%,transparent);
--{p}-ring:color-mix(in srgb,var(--{p}-hoofd) 28%,transparent);--{p}-lijn-a:color-mix(in srgb,var(--{p}-accent) 35%,transparent);
--{p}-lijn-b:color-mix(in srgb,var(--{p}-hoofd) 18%,transparent);--{p}-lijn-c:color-mix(in srgb,var(--{p}-hoofd) 34%,transparent);
--{p}-streep:color-mix(in srgb,var(--{p}-hoofd) 27%,transparent);--{p}-rand:color-mix(in srgb,var(--{p}-hoofd) 20%,transparent);
--{p}-ring-accent:color-mix(in srgb,var(--{p}-accent) 38%,transparent);--{p}-halo:color-mix(in srgb,var(--{p}-accent) 7%,transparent);
--{p}-kaart-a:color-mix(in srgb,var(--{p}-zand) 17%,transparent);--{p}-kaart-b:color-mix(in srgb,var(--{p}-vlak) 90%,transparent);--{p}-kaart-c:color-mix(in srgb,var(--{p}-hoofd) 8%,transparent)}}
.{p},.{p} *,.{p} *::before,.{p} *::after{box-sizing:border-box}
.{p}{width:100%;margin:0;padding:0;color:var(--{p}-tekst);font-family:inherit;font-size:1rem;line-height:1.65}
.{p} .{p}__lijst{position:relative;display:grid;gap:96px;margin:0;padding:0;list-style:none}
.{p} .{p}__lijst::before{content:"";position:absolute;top:10px;bottom:10px;left:50%;width:1px;background:linear-gradient(180deg,var(--{p}-lijn-a) 0%,var(--{p}-lijn-b) 54%,var(--{p}-lijn-c) 100%);transform:translateX(-.5px)}
.{p} .{p}__item{position:relative;display:grid;grid-template-columns:minmax(0,1fr) 106px minmax(0,1fr);align-items:start;margin:0;padding:0;list-style:none}
.{p} .{p}__item::before{content:"";position:relative;z-index:2;grid-column:2;grid-row:1;justify-self:center;width:9px;height:9px;margin-top:38px;border:2px solid var(--{p}-vlak);border-radius:50%;background:var(--{p}-marker-voorbij);box-shadow:0 0 0 1px var(--{p}-ring)}
.{p} .{p}__item--aankomend::before{background:var(--{p}-accent);box-shadow:0 0 0 1px var(--{p}-ring-accent),0 0 0 7px var(--{p}-halo)}
.{p} .{p}__kaart{position:relative;grid-row:1;width:min(100%,492px);margin:0}
.{p} .{p}__item:nth-child(odd) .{p}__kaart{grid-column:1;justify-self:end;margin-right:8px}
.{p} .{p}__item:nth-child(even) .{p}__kaart{grid-column:3;justify-self:start;margin-left:8px}
.{p} .{p}__item:nth-child(4n+2) .{p}__kaart{width:min(88%,438px)}
.{p} .{p}__item:nth-child(4n+3) .{p}__kaart{width:min(96%,470px)}
.{p} .{p}__item:nth-child(4n) .{p}__kaart{width:min(90%,450px)}
.{p} .{p}__item--aankomend .{p}__kaart{padding:32px 32px 34px;background:linear-gradient(135deg,var(--{p}-kaart-a),var(--{p}-kaart-b) 58%,var(--{p}-kaart-c))}
.{p} .{p}__item--voorbij .{p}__kaart{padding:30px 44px 0 8px}
.{p} .{p}__meta{display:flex;flex-wrap:wrap;align-items:center;gap:0 12px;margin:0 0 16px;color:var(--{p}-hoofd);font-size:.75rem;letter-spacing:.08em;line-height:1.5;text-transform:uppercase}
.{p} .{p}__status{display:inline-flex;align-items:center;gap:12px;color:var(--{p}-hoofd)}
.{p} .{p}__status::after{content:"";display:block;width:18px;height:1px;background:var(--{p}-streep)}
.{p} .{p}__item--aankomend .{p}__status{color:var(--{p}-accent-tekst)}
.{p} .{p}__titel{margin:0;color:var(--{p}-hoofd);font-family:inherit;font-size:clamp(1.5rem,1.1rem + 1.2vw,2.0625rem);font-weight:400;letter-spacing:-.028em;line-height:1.18;text-transform:none}
.{p} .{p}__item--voorbij .{p}__titel{color:var(--{p}-titel-voorbij)}
.{p} .{p}__tijd,.{p} .{p}__locatie{max-width:none;margin:9px 0 0;color:var(--{p}-tekst-2);font-size:.8125rem;line-height:1.5}
.{p} .{p}__locatie{margin-top:2px}
.{p} .{p}__item--voorbij .{p}__tijd,.{p} .{p}__item--voorbij .{p}__locatie{color:var(--{p}-tekst-3)}
.{p} .{p}__tekst{margin:18px 0 0;color:var(--{p}-tekst-2);font-size:1rem;line-height:1.7}
.{p} .{p}__item--voorbij .{p}__tekst{color:var(--{p}-tekst-3)}
.{p} .{p}__tekst p{max-width:none;margin:0 0 .8em}
.{p} .{p}__tekst p:last-child{margin-bottom:0}
.{p} .{p}__tekst a{color:var(--{p}-hoofd);text-decoration:underline;text-underline-offset:.15em}
.{p} .{p}__link{display:inline-flex;align-items:center;gap:10px;min-height:44px;margin-top:14px;color:var(--{p}-hoofd);font-size:.875rem;line-height:1.3;text-decoration:none}
.{p} .{p}__link span{color:var(--{p}-accent);transition:transform .22s ease}
.{p} .{p}__link:hover span,.{p} .{p}__link:focus-visible span{transform:translateX(5px)}
.{p} .{p}__link:focus-visible,.{p} .{p}__tekst a:focus-visible{outline:2px solid var(--{p}-hoofd);outline-offset:3px;border-radius:2px}
.{p} .{p}__melding{display:flex;flex-wrap:wrap;align-items:center;gap:8px 16px;max-width:none;margin:0;padding:18px 22px;border:1px solid var(--{p}-rand);border-radius:6px;color:var(--{p}-tekst-2);font-size:.9375rem;line-height:1.6}
.{p} .{p}__knop{display:inline-flex;align-items:center;min-height:44px;padding:0 20px;border:1px solid var(--{p}-hoofd);border-radius:7px;color:var(--{p}-hoofd);font-size:.875rem;line-height:1.2;text-decoration:none;transition:background-color .2s ease}
.{p} .{p}__knop:hover{background:var(--{p}-kaart-c)}
.{p} .{p}__knop:focus-visible{outline:2px solid var(--{p}-hoofd);outline-offset:3px}
.{p} .{p}__lijst + .{p}__melding{margin-top:48px}
.{p} .{p}__melding + .{p}__melding{margin-top:10px}
@media (max-width:1000px){
.{p} .{p}__lijst{gap:64px}
.{p} .{p}__lijst::before{left:26px;transform:none}
.{p} .{p}__item{grid-template-columns:52px minmax(0,1fr)}
.{p} .{p}__item::before{grid-column:1}
.{p} .{p}__item:nth-child(n) .{p}__kaart{grid-column:2;justify-self:start;width:100%;margin:0}
.{p} .{p}__item--aankomend .{p}__kaart{padding:26px 22px 28px}
.{p} .{p}__item--voorbij .{p}__kaart{padding:26px 0 0}
}
@media (prefers-reduced-motion:reduce){.{p} .{p}__link span,.{p} .{p}__knop{transition:none}}
BGTLCSS;

		$vervang = array(
			'{p}'              => $i['css_voorvoegsel'],
			'{c_hoofd}'        => $i['kleur_hoofd'],
			'{c_tekst}'        => $i['kleur_tekst'],
			'{c_accent}'       => $i['kleur_accent'],
			'{c_accent_tekst}' => $i['kleur_accent_tekst'],
			'{c_zand}'         => $i['kleur_zand'],
			'{c_vlak}'         => $i['kleur_vlak'],
		);
		return trim( preg_replace( '/\s+/', ' ', strtr( $css, $vervang ) ) );
	}
}

/** De shortcode. Mag nooit een foutmelding of lege pagina veroorzaken. */
if ( ! function_exists( 'bgtl_shortcode' ) ) {
	function bgtl_shortcode( $atts = array() ) {
		static $css_klaar = array();
		try {
			$i    = bgtl_normaliseer( bgtl_instellingen() );
			$atts = shortcode_atts( array( 'aankomend' => '', 'voorbij' => '' ), $atts, 'agenda_tijdlijn' );
			if ( '' !== $atts['aankomend'] ) {
				$i['max_aankomend'] = max( 0, min( 100, (int) $atts['aankomend'] ) );
			}
			if ( '' !== $atts['voorbij'] ) {
				$i['max_voorbij'] = max( 0, min( 100, (int) $atts['voorbij'] ) );
			}

			$nu      = time();
			$beheer  = current_user_can( 'manage_options' );
			$data    = bgtl_haal_events( $i, $nu, false );
			$html    = bgtl_html( $data, $i, $nu, $beheer );

			$p   = $i['css_voorvoegsel'];
			$css = '';
			if ( empty( $css_klaar[ $p ] ) ) {
				$css_klaar[ $p ] = true;
				$css = '<style id="' . esc_attr( $p ) . '-css">' . bgtl_css( $i ) . '</style>';
			}
			return $css . $html;
		} catch ( \Throwable $e ) {
			return '<p>' . esc_html( 'De agenda is op dit moment niet beschikbaar.' ) . '</p>';
		}
	}
}

add_shortcode( 'agenda_tijdlijn', 'bgtl_shortcode' );
add_action( 'template_redirect', 'bgtl_verwerk_verversen' );
