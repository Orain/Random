<?php

/**
 * @package MediaWiki
 * @subpackage Extensions
 * @author darklama
 * @license http://www.gnu.org/licenses/gpl.html
 * @licence http://creativecommons.org/licenses/by-sa/3.1
 */

if ( !defined( 'MEDIAWIKI' ) ) {
  echo( "This is a mediaWiki extension and cannot be run standalone.\n" );
  die( -1 );
}

$wgHooks['LanguageGetMagic'][] = 'efRandomLanguageGetMagic';
$wgHooks['ParserFirstCallInit'][] = 'efRandomExtension';
$wgExtensionCredits['other'][] = array(
  'name'        => 'Random' ,
  'version'     => '0.3',
  'description' => 'Includes a random line of wikitext from given items',
  'author'      => '[https://en.wikiversity.org/wiki/User:Darklama darklama]',
  'url'         => 'https://www.mediawiki.org/wiki/Extension:Random',
);

function efRandomLanguageGetMagic( $magicWords, $langCode ) {
  $magicWords['random'] = array( 0, 'random' );
  return true;
}

function efRandomExtension( $parser ) {
  $parser->setHook( 'random', 'renderRandom' );
  $parser->setFunctionHook( 'random', 'randomObj', SFH_OBJECT_ARGS );
  return true;
}

function renderRandom( $input, $argv, $parser ) {
  $count = intval( $argv['count'] );
  $result = '';
  $format = '%ITEM%';
  $items = array();
  $elements = array( 'item', 'format' );

  $text = Parser::extractTagsAndParams( $elements, $input, $matches );

  foreach ( $matches as $marker => $data ) {
    list( $element, $content, $params, $tag ) = $data;
    if ( $element === 'item' ) {
      $content = trim( $content, " \t" );
      $content = trim( $content, "\n" );
      $items[] = str_replace( '%ITEM%', $content, $format );
    } else if ( $element === 'format' ) {
      if ( !empty( $content ) && preg_match( '/%ITEM%/', $content ) != 0 ) {
        $format = str_replace( 'Â«', '<', $content );
        $format = str_replace( 'Â»', '>', $format );
        $format = trim( $format, " \t" );
        $format = trim( $format, "\n" );
        $format = str_replace( '\n', "\n", $format );
      }
    }
  }

  $entries = count( $items );
  if ( $entries == 0) {
    return '';
  }
  if ( $count <= 0 ) {
    $count = 1;
  } else if ( $count > $entries ) {
    $count = $entries;
  }

  $keys = array_rand( $items, $count );

  if ( is_array( $keys ) ) {
    foreach ( $keys as $key ) {
      $result .= $parser->recursiveTagParse( $items[$key] );
    }
  } else {
    $result = $parser->recursiveTagParse( $items[$keys] );
  }

  $parser->disableCache();
  return $result;
}

function randomObj( $parser, $frame, $args ) {
  $count = isset($args[0]) ? intval(trim( $frame->expand( $args[0] ) ) ) : 1;
  $entries = count($args);
  $result = '';

  if ( $entries <= 0 ) {
    return '';
  } else if ( $entries == 1 ) {
    return trim( $frame->expand( $args[0] ) );
  } else if ( $count > ($entries-1) ) {
    $count = $entries-1;
  } else if ( $count <= 0 ) {
    $count = 1;
  }

  unset($args[0]);
  $keys = array_rand( $args, $count );

  if ( is_array( $keys ) ) {
    foreach ( $keys as $key ) {
      $result .= ltrim( $frame->expand( $args[$key] ) );
    }
  } else {
    $result = ltrim( $frame->expand( $args[$keys] ) );
  }

  $parser->disableCache();
  return rtrim( $result );
}
