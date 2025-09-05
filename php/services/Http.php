<?php
final class Http {
  public static function headOrGetOk(string $url, int $timeout=8): array {
    if (!is_string($url) || $url === '') {
      return ['ok'=>false,'status'=>0,'error'=>'empty-url','url'=>$url];
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
      CURLOPT_NOBODY=>true, CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>$timeout,
      CURLOPT_FOLLOWLOCATION=>true, CURLOPT_SSL_VERIFYHOST=>2, CURLOPT_SSL_VERIFYPEER=>true,
      CURLOPT_USERAGENT=>'vh-preflight/1.0',
    ]);
    curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($code === 405 || $code === 403 || $code === 0) {
      $ch = curl_init($url);
      curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>$timeout, CURLOPT_FOLLOWLOCATION=>true,
        CURLOPT_RANGE=>'0-32', CURLOPT_SSL_VERIFYHOST=>2, CURLOPT_SSL_VERIFYPEER=>true,
        CURLOPT_USERAGENT=>'vh-preflight/1.0',
      ]);
      curl_exec($ch);
      $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
      $err2 = curl_error($ch);
      curl_close($ch);
      return ['ok'=>($code>=200 && $code<300), 'status'=>$code, 'error'=>$err ?: $err2, 'url'=>$url];
    }

    return ['ok'=>($code>=200 && $code<300), 'status'=>$code, 'error'=>$err, 'url'=>$url];
  }
}
