<?PHP

/*
# It's torr! v20221001

공개 토렌트 사이트의 게시물을 RSS 형태로 변환

### 적용 방법

torr.php를 웹서비스 루트에 torr 디렉토리를 만들고 복사한다.  

시놀로지는 웹서비스를 활성화하고 web/torr/torr.php로 복사한다.

### 사용 방법

예) http://your-server-ip/torr/torr.php?b=ent&k=놀면 뭐하니

 - b: 검색할 게시판으로써 미리 정의된 것만 사용 가능하다. 게시판을 지정하지 않으면 예능, 드라마, 다큐에서 통합 검색한다. 지원하는 게시판은 예능(ent), 드라마(drama), 다큐(docu), 미드(mid), 기타(etc)이다.
- k: 검색어(예, 놀면 뭐하니)이다. 생략 시 해당 게시판 첫 페이지의 목록을 가져온다.

### 업데이트

- torr.php는 자동 업데이트를 지원한다. 

- 업데이트 시 torr.php 자체가 변경되므로 유지해야 할 설정이 있는 경우 UserConfig.php 파일에 정의하면 된다.
  아래는 set 정보를 UserConfig.php에 정의한 예시이다.

  ```php
  <?PHP
  
  # 검색어 Set: 세트로 등록된 이름을 검색키워드로 사용하면 한번에 다중 검색을 실시한다.
  $KEYWORDS = array(
      "set01" => array("라디오스타", "놀면 뭐하니 720p-NEXT", "나 혼자 산다 720p-NEXT"),
      "set02" => array("바퀴달린 집 720p-NEXT", "유 퀴즈 온 더 블럭 720p-NEXT"),
      "set03" => array("놀면 뭐하니", "나 혼자 산다", "라디오스타", "여자들의 은밀한 파티")
  );
  ```

- T사이트가 죽어버린 후 며칠 기다리다 보면 작동할 될 수도 있다.

- 최신 버전 체크는 하루에 한번 한다.

- 자동 업데이트를 중지하려면 define('AUTO_UPDATE', false);로 변경한다.

### 고급 활용

- 검색어는 수직바(|)로 구분하여 여러개를 입력할 수 있다. 
  예) http://your-server-ip/torr/torr.php?b=ent&k=놀면 뭐하니|라디오 스타|나 혼자 산다
- 검색어는 미리 정의한 세트명을 이용할 수도 있다. 
  예)  http://your-server-ip/torr/torr.php?b=ent&k=set01
- 작동이 제대로 되지 않으면 $logger->setLevel('DEBUG');로 변경하고 로그 파일(torr.log)을 살펴본다.
- 정보를 수집할 사이트가 바뀌면 $CONFIG를 수정한다.

### 변경 이력

 *   20221001 - 토렌트 사이트 변경
 *   20220924 - 토렌트 사이트 변경
 *   20220502 - 토렌트 사이트 변경
 *   20211017 - 토렌트 사이트 변경
 *   20211016 - 토렌트 사이트 변경
 *   20210919 - 자동업데이트 오류 수정
 *   20210916 - 자동업데이트 오류 수정
 *   20210914 - 토렌트 사이트 변경
 *   20210913 - 토렌트 사이트 변경
 *   20210806 - 도메인 자동 변경 오류 수정
 *   20210805 - 도메인 자동 변경 기능 추가, 토렌트 사이트 변경
 *   20210211 - 사이트 주소 변경
 *   20200817 - 죽었던 torr의 부활
 *   20170416 - 사이트 정보 자동 업데이트 기능 추가
 *   20170425 - 자동 업데이트 오류 수정 및 첫페이지에서 마그넷 링크 검색 기능 추가
 *   20170515 - 마그넷 링크가 다른 페이지에 존재하는 경우에도 처리하도록 기능 추가
 *   20170613 - 사이트 정보 업데이트 태그(code > pre) 변경. html의 선처리 추가.
 
 */

/*
  github 배포 전 base64 인코딩값을 확인하려면 아래 명령어를 실행한다.
  php torr.php base64
*/
########################################################################################################################
## 토렌트 사이트 정보
########################################################################################################################
$CONFIG = array(
    "ent" => array(     # TV예능
        "https://torrentsir76.com/bbs/board.php?bo_table=entertain&sca=&sfl=wr_subject&sop=and&stx={k}"
    ),
    "drama" => array(   # TV드라마
        "https://torrentsir76.com/bbs/board.php?bo_table=drama&sca=&sfl=wr_subject&sop=and&stx={k}",
    ),
    "docu" => array(    # TV다큐/시사
        "https://torrentsir76.com/bbs/board.php?bo_table=tv&sca=&sfl=wr_subject&sop=and&stx={k}"
    ),
    "mid" => array(     # 외국드라마
        // TV드라마에 포함
    ),
    "etc" => array(     # 스포츠, 애니 등
        "https://torrentsir76.com/bbs/board.php?bo_table=ani&sca=&sfl=wr_subject&sop=and&stx={k}"
    ), 

    # 게시판을 지정하지 않은 경우 검색할 게시판 목록
    "all" => array("ent", "drama", "docu"),

    # 글목록, 다운로드 링크 검색을 위한 정규식 패턴. (변경자: s-단일 라인으로 처리, m-여러 라인으로 처리, i-대소문자 무시)
    "_page_link_preprocess" => array(),
    "_page_link" => '!<div class="wr-subject">.*?<a href="(?P<link>.+?)".*?>(?P<title>.+?)</a>!si',

    # 마그넷링크(토렌트파일)가 있는 페이지의 html을 받은 후 미리 변경할 내용들: 예) array("/magnet_link\('/i", "href='magnet:?xt=urn:btih:"),
    "_down_link_preprocess" => array(),

    # 마그넷 형식을 찾는다.
    "_down_link" => 'magnet',

    # 페이지 내에 마그넷 링크를 노출하지 않고 특정 URL로 요청하면 주는 경우가 있다.
    # 이런 유형의 요청 URL을 찾기 위한 패턴을 미리 정의해야 한다. 
    "_magnet_follow_link" => ''
);


# 검색어 Set: 세트로 등록된 이름을 검색키워드로 사용하면 한번에 다중 검색을 실시한다.
$KEYWORDS = array(
    "set01" => array("라디오스타 720p-NEXT", "놀면 뭐하니 720p-NEXT", "나 혼자 산다 720p-NEXT"),
    "set02" => array("유 퀴즈 온 더 블럭 720p-NEXT")
);

# curl 요청 시 사용할 proxy 주소. ip:port
$PROXY = '';

define('VERSION', 'v20221001');  # app version
define('AUTO_UPDATE', true);  # 자동 업데이트 기능 사용
define('MAGNET_CACHE_CONSERVE_DAYS', 450);   # 마그넷 캐시 보존일수
define('UPDATE_URL', 'https://raw.githubusercontent.com/bjtower/torr/master/torr.encoded');  # torr 자동 업데이트 url

$logger = new Mylogger();
$logger->setLevel('DEBUG');  # DEBUG, INFO, WARN, ERROR를 사용할 수 있다.

# 사용자 설정 파일을 읽어서 기본 설정을 갱신한다.
# 스크립트 파일은 자동 업데이트가 되기 때문에 보존이 필요한 설정은 UserConfig.php에 정의한다.
if (file_exists('UserConfig.php')) include 'UserConfig.php';


########################################################################################################################
## Common util functions
########################################################################################################################

class MyLogger
{
    private $logf = null;
    private $level = 'INFO';

    public function __construct()
    {
        date_default_timezone_set('Asia/Seoul');
        $this->logf = basename(__FILE__, '.php') . '.log';
    }

    public function setLevel($level)
    {
        $this->level = $level;
    }

    public function debug($msg)
    {
        if ($this->level == 'DEBUG') {
            $this->_log('D', $msg);
        }
    }

    public function info($msg)
    {
        if ($this->level == 'INFO' || $this->level == 'DEBUG') {
            $this->_log('I', $msg);
        }
    }

    public function warn($msg)
    {
        if ($this->level == 'WARN' || $this->level == 'INFO' || $this->level == 'DEBUG') {
            $this->_log('W', $msg);
        }
    }

    public function error($msg)
    {
        $this->_log('E', $msg);
    }

    private function _log($level, $msg)
    {
        $t = microtime(true);
        $milli = sprintf("%03d", ($t - floor($t)) * 1000);
        $dt = sprintf("%s.%s", date('Y-m-d H:i:s'), $milli);
        $bt = debug_backtrace();
        $func = isset($bt[2]) ? $bt[2]['function'] : '__main__';
        $caller = $bt[1];
        $file = basename($caller['file']);
        $output = sprintf("%s %s <%s:%s:%d> %s\n", $dt, $level, $file, $func, $caller['line'], print_r($msg, true));
        error_log($output, 3, $this->logf);
    }
}


# User-Agent와 referer를 유지하는 웹 탐색기
function curl_fetch($url, $init = false)
{
    global $logger;
    global $PROXY;
    static $ch = null;
    static $cnt = 0;

    (++$cnt) % 10 == 0 && sleep(2);  # 단시간 내에 너무 많이 조회하면 차단당할 수도 있다

    $cookie_nm = './cookie.txt';

    if ($ch == null || $init == true) {
        $ch != null && curl_close($ch);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.127 Safari/537.36');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIESESSION, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_nm);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_HEADER, true);

        if ($PROXY) {
            curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
            curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_PROXY, $PROXY);

            $logger->info("Proxy setting applied. Proxy info: $PROXY");
        }

    }

    curl_setopt($ch, CURLOPT_URL, $url);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    list($header, $body) = explode("\r\n\r\n", $response, 2);

    $size = strlen($body);

    $logger->debug("cURL fetch http_code:[$http_code] url:[$url]  size:[$size]");
    // $logger->debug($header);
    // $logger->debug($body);

    return array($http_code, $header, $body);
}


# 상대주소를 $base 기반의 절대주소로 반환한다.
function url_join($base, $relative_url)
{
    /* return if already absolute URL */
    if (parse_url($relative_url, PHP_URL_SCHEME) != '') return $relative_url;

    /* queries and anchors */
    if ($relative_url[0] == '#' || $relative_url[0] == '?') return $base . $relative_url;

    /* parse base URL and convert to local variables: $scheme, $host, $path */
    $parsed = parse_url($base);
    $scheme = $parsed['scheme'];
    $host = $parsed['host'];
    $path = isset($parsed['path']) ? $parsed['path'] : '';

    /* remove non-directory element from path */
    $path = preg_replace('#/[^/]*$#', '', $path);

    /* destroy path if relative url points to root */
    if ($relative_url[0] == '/') $path = '';

    /* dirty absolute URL */
    $abs = "$host$path/$relative_url";

    /* replace '//' or '/./' or '/foo/../' with '/' */
    $re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');
    for ($n = 1; $n > 0; $abs = preg_replace($re, '/', $abs, -1, $n)) {
    }

    /* absolute URL is ready! */
    return $scheme . '://' . $abs;
}


# 다양한 소스에서 magnet link를 찾는다.
# (시도1) $source가 url(http로 시작)인 경우 header와 html을 받는다.
# (시도2) 어떤 사이트는 magnet을 직접 노출하지 않고 특정 url로 접근했을 때 302 응답과 함께 header의 Location에 담아서 내려보내기도 한다.
#         따라서 header에서 Location 항목에 magnet 링크가 있는지부터 확인하다.
# (시도3) $source가 url이 아니거나 시도2에서 받은 html이 있는 경우 magnet link pattern으로 찾는다.
function find_magnet($source)
{
    global $logger;
    $html = null;
    $magnet = null;

    if (preg_match('/^http/si', $source, $match)) {
        list($http_code, $header, $html) = curl_fetch($source);
        if (preg_match('/^Location: (magnet:.+)$/mi', $header, $match)) {
            $magnet = $match[1];
            $logger->debug("Location 헤더에서 magnet 링크를 찾았습니다: " . $magnet);
        }
    } else {
        $html = $source;
    }

    if (preg_match('/href=[\'"]?(magnet:.+?)[\s\'">]/si', $html, $match)) {
        $magnet = $match[1];
        $logger->debug("HTML 페이지에서 magnet 링크를 찾았습니다: " . $magnet);
    }

    return $magnet;
}


# url 여부 판단
function is_url($url)
{
    return isset(parse_url($url)['host']);
}


# 정규식을 이용한 변경 처리
function preprocess($text, $patterns)
{
    global $logger;
    $text_bf = $text;
    $text_af = $text;

    $count = 0;
    for ($i = 0; $i < count($patterns); $i += 2) {
        $text_af = preg_replace($patterns[$i], $patterns[$i + 1], $text_bf, -1, $count);
        if ($count == 0) {
            $logger->warn('preg_replace pattern is not found: ' . $patterns[$i]);
            $text_af = $text_bf;
        } else {
            $bf_len = strlen($text_bf);
            $af_len = strlen($text_af);
            $logger->debug("text changed for $count times. Length of Before text is $bf_len and after text is $af_len");
            $text_bf = $text_af;
        }
    }

    # DEV_MODE && $logger->debug(('AFTER PAGE Preprocess');
    # DEV_MODE && $logger->debug(($text);

    return $text_af;
}


# url 정규화: &amp;를 &로 변경하여 반환한다.
function url_normalize($url)
{
    return str_replace('&amp;', '&', $url);
}


# 정규식을 입력받아 파일의 내용을 변경한다.
function file_regex_update($filename, $regex_fr, $regex_to)
{
    $origin = file_get_contents($filename);
    $updated = preg_replace($regex_fr, $regex_to, $origin);
    file_put_contents($filename, $updated);
}

########################################################################################################################
## Business functions
########################################################################################################################

# torr.php 파일 자동업데이트
#  - 자동 업데이트 후 파일사이즈가 0바이트가 되는 오류가 종종 발생함 -> 다운로드 받은 크기를 확인 후 갱신토록 함
function self_update()
{
    global $logger;

    # 업데이트 실행 여부 확인
    if (!AUTO_UPDATE) return;

    # 하루에 한번만 업데이트 체크를 해보자
    $update_check_file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'torr.updatecheck';
    if (@file_get_contents($update_check_file) == date('Ymd')) return;
    file_put_contents($update_check_file, date('Ymd'));

    # 원격 저장소에서 토렌트 사이트 정보 받아오기 (코드 보안을 위해 base64로 인코딩된 내용으로 받는다)
    list($http_code, $header, $remote_base64) = curl_fetch(UPDATE_URL);

    if ($http_code != 200) {
        $logger->warn("업데이트 파일 다운로드 오류입니다. 응답코드: $http_code");
        return;
    }

    # 현재 파일과 비교하기
    $local_base64 = base64_encode(file_get_contents(__FILE__));

    if ($local_base64 != $remote_base64) {
        $logger->info("torr가 변경되어 업데이트를 시작합니다.");

        # 현재 스크립트 파일 백업하기
        !file_exists('backup') && mkdir('backup');
        $backupf = sprintf("backup/%s.%s.php", basename(__FILE__, '.php'), VERSION);
        copy(__FILE__, $backupf);
        $logger->info(sprintf("업데이트 전에 현재 torr를 백업했습니다: %s -> %s", basename(__FILE__), $backupf));

        # base64로 인코딩 된 정보 해석하기
        $decoded_source = base64_decode($remote_base64);

        # 현재 스크립트 업데이트!
        file_put_contents(__FILE__, $decoded_source);   # 변경된 내용 기록

        $logger->info("torr를 업데이트했습니다. 다음번 요청 시 반영됩니다.");
    } else {
        $logger->info("현재 torr가 최신 버전입니다. 내일 다시 확인합니다.");
    }
}


# 파라미터를 분석하여 탐색할 게시판 주소 목록을 생성한다.
function parse_param()
{
    global $logger;

    $logger->info(sprintf("Param: b=[%s] k=[%s]", isset($_GET['b']) ? $_GET['b'] : null, isset($_GET['k']) ? $_GET['k'] : null));

    $conf = $GLOBALS['CONFIG'];
    $keyw = $GLOBALS['KEYWORDS'];

    $b = isset($_GET['b']) ? trim($_GET['b']) : 'all';
    if (!isset($conf[$b])) {
        $logger->error($_GET['b'] . "는 유효하지 않은 게시판입니다.");
        return;
    }

    $boards = array();
    foreach ($conf[$b] as $item) {
        if (is_url($item)) {
            $boards[] = array('category' => $b, 'url' => $item);
        } else {
            foreach ($conf[$item] as $item2) {
                $boards[] = array('category' => $item, 'url' => $item2);
            }
        }
    }

    $k = isset($_GET['k']) ? trim($_GET['k']) : '';
    $keywords = isset($keyw[$k]) ? $keyw[$k] : explode('|', $k);
    $keywords = str_replace(' ', '%20', $keywords);

    if (count($keywords) > 1) {
        $logger->info(sprintf("다중검색 키워드: %d개 (%s)", count($keywords), join(", ", $keywords)));
    }

    $fetch_urls = array();
    foreach ($boards as $board) {
        foreach ($keywords as $keyword) {
            $fetch_urls[] = array('category' => $board['category'], 'url' => str_replace('{k}', $keyword, $board['url']));  # 검색어(키워드) 조합
        }
    }

    $logger->debug($fetch_urls);

    return $fetch_urls;
}


# 토렌트 사이트 도메인은 주기적으로 변경되기 때문에 접속이 불가한 경우엔 다음 도메인을 찾아본다.
# 작동 원리
#   - 아직 도메인이 살아있지만 곧 변경될 예정인 경우 응답헤더의 Location에 변경될 도메인을 응답하는 경우가 있음
#   - 도메인에 일련번호가 있는 경우 번호를 증가시키면서 다음번 도메인을 찾아본다.  예) www.torrentdia2.com > www.torrentdia3.com
#   - 변경된 도메인은 UserConfig.php에 기록해두고 다음부터는 해당 정보를 사용
# 변경 순서
#   1) UserConfig.php에 변경전후 도메인 정보가 있다면 해당 도메인 사용
#   2-1) 도메인이 유효한지 실제로 접속을 시도하여 점검 후 Location 헤더가 있다면 도메인 변경
#   2-2) http 응답코드가 정상(200)이 아니라면 도메인에 포함된 일련번호를 증가시키면서 다음 도메인 검색
#   3) 변경 정보를 UserConfig.php에 기록
# 
function update_domain($board_urls)
{
    global $logger;
    global $OLD_DOMAIN, $NEW_DOMAIN;

    # 1. 첫번째 url에서 도메인 정보 추출
    $parsed = parse_url($board_urls[0]['url']);
    $current_domain = $parsed['host'];   # $CONFIG에 기록된 도메인
    $changed_domain = $current_domain;   # 최종 변경된 도메인

    $logger->debug("\$current_domain = $current_domain");

    # 2. UserConfig.php에 기록된 변경전도메인($OLD_DOMAIN)과 일치한다면 도메인 변경 처리
    if (isset($OLD_DOMAIN) && $OLD_DOMAIN == $current_domain) {
        $changed_domain = $NEW_DOMAIN;
        $logger->debug("UserConfig.php 파일의 NEW_DOMAIN 정보를 사용함: $NEW_DOMAIN");
    }

    # 3. 실제 fetch하여 도메인이 변경됐는지 확인
    $check_url = $parsed["scheme"] . '://' . $changed_domain;
    list($http_code, $header, $html) = curl_fetch($check_url);

    # Location 헤더가 있다는 것은 도메인이 변경됐다는 뜻
    if (preg_match('/^Location: (.+)$/mi', $header, $match)) {
        $changed_domain = parse_url($match[1])['host'];
        $logger->info("Location 헤더에서 신규 도메인을 찾았습니다: " . $changed_domain);
    }

    # 그 외 정상 응답을 못받은 경우엔 도메인에 있는 일련번호를 찾아 하나씩 증가하면서 도메인을 찾아본다.
    # 예) https://torrentview38.com/ => 38을 39, 40 등으로 변경하면서 새로운 도메인을 찾는다.
    else if ($http_code != 200) {
        $new_domain_founded = false;
        if (preg_match('/^(.+?)([\d]{1,3})(\..+)$/', $changed_domain, $match)) {
            $domain_01 = $match[1];
            $domain_02 = intval($match[2]);
            $domain_03 = $match[3];
            
            for($i=0; $i<10; $i++) {
                $domain_02++;
                $try_domain =  $domain_01 . $domain_02 . $domain_03;
                $try_url = $parsed["scheme"] . '://' . $try_domain;
                
                list($http_code2, $header2, $html2) = curl_fetch($try_domain);
                
                $logger->info("Tried to check if $try_url is alive. and http_code is $http_code2");
                
                if ($http_code2 == 200) {
                    $new_domain_founded = true;
                    $changed_domain = $try_domain;
                    $logger->info("변경된 도메인을 찾았습니다: " . $changed_domain);
                    break;
                }
            }
        }

        if (!$new_domain_founded) {
            $logger->error("torrent 사이트 [$current_domain] 가(이) 죽은 듯 합니다. 종료합니다.");
            error_response("torrent 사이트 [$current_domain] 가(이) 죽은 듯 합니다. 종료합니다.");
            self_update();
            exit(0);
        }
    }

    # 4. url 목록에서 도메인 변경
    if ($current_domain != $changed_domain) {
        foreach ($board_urls as &$burl) {
            $burl['url'] = str_replace($current_domain, $changed_domain, $burl['url']);
        }

        $logger->info("도메인을 변경합니다: " . $current_domain . "  >  " . $changed_domain);
        $logger->debug($board_urls);
    }

    # 5. UserConfig에 변경된 도메인 정보 기록
    if ($current_domain != $changed_domain) {
        if (!file_exists('UserConfig.php')) {
            $contents = "<?PHP\n\n\$OLD_DOMAIN = '$current_domain';\n\$NEW_DOMAIN = '$changed_domain';\n";
            file_put_contents('UserConfig.php', $contents);
            $logger->info("UserConfig.php에 변경된 도메인 정보를 추가함");
        }
        else {
        if (!isset($OLD_DOMAIN)) {
            file_put_contents('UserConfig.php', "\n\$OLD_DOMAIN = '$current_domain';\n", FILE_APPEND);
            $logger->info("UserConfig.php에 도메인 정보를 추가함: \$OLD_DOMAIN = '$current_domain'");
        }
        else if ($OLD_DOMAIN != $current_domain) {
            file_regex_update('UserConfig.php', '/^\$OLD_DOMAIN = .+/im', "\$OLD_DOMAIN = '$current_domain';");
            $logger->info("UserConfig.php에 도메인 정보를 갱신함: \$OLD_DOMAIN = '$current_domain'");
        }

        if (!isset($NEW_DOMAIN)) {
            file_put_contents('UserConfig.php', "\n\$NEW_DOMAIN = '$changed_domain';\n", FILE_APPEND);
            $logger->info("UserConfig.php에 도메인 정보를 추가함: \$NEW_DOMAIN = '$changed_domain'");
        } 
        else if ($NEW_DOMAIN != $changed_domain) {
            file_regex_update('UserConfig.php', '/^\$NEW_DOMAIN = .+/im', "\$NEW_DOMAIN = '$changed_domain';");
            $logger->info("UserConfig.php에 도메인 정보를 갱신함: \$NEW_DOMAIN = '$changed_domain'");
            }
        }
    }

    return $board_urls;
}


# 토렌트 게시판들에서 아이템(제목, 페이지링크)를 추출한다.
function get_items($board_urls)
{
    global $logger;

    $self = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . parse_url($_SERVER['REQUEST_URI'])['path'];
    $pattern = $GLOBALS['CONFIG']['_page_link'];
    $replace_patterns = $GLOBALS['CONFIG']['_page_link_preprocess'];
    $is_magnet = $GLOBALS['CONFIG']['_down_link'] == 'magnet';
    $items = array();

    $bcnt = 0;

    foreach ($board_urls as $burl) {
        $bcnt++;
        $url = $burl['url'];
        $category = $burl['category'];

        list($http_code, $header, $html) = curl_fetch($url);

        if ($http_code != 200) {
            $logger->error("Error. Fetch from $url returned http_code $http_code");
            $logger->error($header);
            continue;
        }

        # html 내용의 일부를 미리 변경
        if ($replace_patterns) {
            $html = preprocess($html, $replace_patterns);
        } else {
            $logger->debug('HTML을 전처리 변경하지 않습니다.');
        }

        preg_match_all($pattern, $html, $matches, PREG_SET_ORDER);

        $cnt = 0;

        foreach ($matches as $match) {

            $cnt++;

            $logger->debug($match);

            # 제목에 포함된 html 태그와 앞뒤 공백을 제거한다.
            $title = trim(html_entity_decode(strip_tags($match['title'])), " \t\n\r\0\x0B\xC2\xA0");    # &nbsp;는 \xC2\xA0로 변환된다.

            # URL 보정: 절대 주소로 변경 후 쿼리문자열에 '&amp;'는 '&'로 변경한다.
            $page = url_join($url, url_normalize($match['link']));
            $logger->debug("Normalized url is $page");

            if ($is_magnet) {
                # 마그넷 링크까지 수집했다면 그걸 사용하고 그렇지 않으면 개별 페이지로 들어가서 마그넷 링크를 찾는다.
                $link = isset($match['magnet']) ? $match['magnet'] : get_magnet_link($page);
            } else {
                $link = $self . '?d=' . base64_encode($page);
            }
            $items[] = array('title' => $title, 'link' => $link, 'page' => $page, 'category' => $category);

            # if ($cnt >= 3) break;  # Todo 개발 완료 후 주석처리해야 함
        }

        $logger->info(sprintf("[%d/%d] %d건의 결과를 찾았습니다. 검색URL: %s", $bcnt, count($board_urls), $cnt, $url));
    }

    $logger->debug($items);

    return $items;
}


# 캐시에서 마그넷 링크를 찾아보고 없으면 url에서 찾아 반환한다.
function get_magnet_link($url)
{
    global $logger;

    $magnet = null;
    $replace_patterns = $GLOBALS['CONFIG']['_down_link_preprocess'];

    # 마그넷 캐시에서 링크를 찾아본다.
    if ($magnet = magnet_cache_control('Query', $url)) {
        $logger->debug("캐시에서 마그넷 링크를 찾았습니다.");
        $logger->debug("요청URL: $url");
        $logger->debug("Magnet: $magnet");
        return $magnet;
    }

    # 웹페이지에서 마그넷 링크를 찾는다.
    list($http_code, $header, $html) = curl_fetch($url);
    if ($http_code != 200) {
        $logger->error("Error. Fetching page from $url returned http_code $http_code");
        return null;
    }

    # html 내용의 일부를 미리 변경
    if ($replace_patterns) {
        $html = preprocess($html, $replace_patterns); 
    }

    $magnet = find_magnet($html);

    if (!$magnet && $GLOBALS['CONFIG']['_magnet_follow_link']) {

        // $logger->debug($html);

        $pattern = $GLOBALS['CONFIG']['_magnet_follow_link'];
        $logger->debug("_magnet_follow_link를 찾아봅니다: $pattern");
        if (preg_match($pattern, $html, $match)) {
            $logger->debug("_magnet_follow_link를 찾았습니다: " . $match['url']);

            $follow_url = url_join($url, $match['url']);
            $logger->debug('마그넷 링크를 찾기위해 다음 페이지로 이동합니다. ' . $follow_url);

            $magnet = find_magnet($follow_url);
        } else {
            $logger->error('오류! _magnet_follow_link 패턴을 찾을 수 없습니다.');
        }
    }

    # 마그넷 캐시에 링크를 업데이트한다.
    $magnet && magnet_cache_control('Update', $url, $magnet);

    # 마그넷 링크를 반환한다.
    return $magnet;
}


# 마그넷 링크의 캐시를 관리한다. (조회, 추가, 저장, 오래된 데이타 삭제)
function magnet_cache_control($action, $url = null, $magnet = null)
{
    global $logger;

    static $magnet_cache = 'N/A', $cache_file = null, $cache_updated = false;
    $key = null;

    # url에서 게시물 고유번호(숫자 4자리 이상)와 캐시파일명을 찾는다.
    if ($url) {
        if (preg_match('/\d\d\d\d+/', $url, $match)) {
            $key = $match[0];
        }
        if ($cache_file == null) {
            $host = parse_url($url)['host'];
            $cache_file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'torr.' . $host . '.cache';
            $logger->debug("캐시파일은 $cache_file 입니다.");
        }
    }

    # 캐시에서 마그넷 링크 조회
    if ($action == 'Query') {
        # 마그넷 캐시가 널이면 파일에서 읽어들인다.
        if ($magnet_cache == 'N/A') {
            $content = @file_get_contents($cache_file);
            if ($content != false) {
                $magnet_cache = unserialize($content);
            } else {
                $magnet_cache = array();
            }
        }

        # 마그넷 캐시에서 key 조회
        if (isset($magnet_cache[$key])) {
            $magnet = $magnet_cache[$key]['magnet'];
            $logger->debug("캐시에서 마그넷 링크를 찾았습니다. key=[$key] magnet=[$magnet]");
            return $magnet;
        }
    }

    # 캐시에 마그넷 링크 추가
    elseif ($action == 'Update') {
        # 마그넷 캐시가 널이면 오류 처리한다.
        if ($magnet_cache == 'N/A') {
            $logger->error("Error. 마그넷 캐시가 생성되지 않아 Update 처리를 할 수 없습니다.");
            self_update();
            exit(1);
        }

        # 마그넷 캐시에 key, magnet 추가
        $magnet_cache[$key] = array('magnet' => $magnet, 'inserted' => time());
        $cache_updated = true;
    }

    # 캐시를 파일로 저장
    elseif ($action == 'Write') {
        if ($cache_updated) {
            # 경량 캐시 유지를 위해 일정일 이상 경과한 데이타는 삭제한다.
            $deleted = 0;
            foreach ($magnet_cache as $key => $item) {
                if ($item['inserted'] + (MAGNET_CACHE_CONSERVE_DAYS * 60 * 60 * 24) < time()) {
                    unset($magnet_cache[$key]);
                    $deleted++;
                }
            }
            $deleted > 0 && $logger->debug("Deleted $deleted cache items.");

            # 캐시 저장
            $content = serialize($magnet_cache);
            file_put_contents($cache_file, $content);
            $cache_updated = false;
        }
    }
}


# 토렌트 아이템(제목, 페이지링크)으로 rss를 생성한다.
function build_rss($items)
{
    global $logger;

    $self = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

    $b = isset($_GET['b']) ? trim($_GET['b']) : '';
    $k = isset($_GET['k']) ? trim($_GET['k']) : '';
    if ($b == '' && $k == '') {
        $title = "It's torr!";
    } else {
        $title = "It's torr! (" . ($b ? $b . ": " : "") . ($k ? $k : "All") . ")";
    }

    $rss  = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n<rss version=\"2.0\">\n";
    $rss .= "<channel><title>$title</title><link>" . htmlentities($self) . "</link><description>A simple torrent feed by torr.</description>\n";
    foreach ($items as $item) {
        if ($item['link']) {
            $rss .= "<item><title><![CDATA[" . $item['title'] . "]]></title><link>" . htmlentities($item['link']) . "</link>";
            $rss .= "<comments><![CDATA[" . htmlentities($item['page']) . "]]></comments><category><![CDATA[" . $item['category'] . "]]></category></item>\n";
        }
    }
    $rss .= "</channel>\n</rss>";

    $logger->info("RSS를 생성했습니다. 건수: " . count($items));
    $logger->debug($rss);

    return $rss;
}


# 클라이언트로 rss를 전송한다.
function response($data)
{
    header('Content-Type: application/rss+xml; charset=utf-8');
    echo $data;
}

# 클라이언트로 오류 메시지를 전송한다.
function error_response($data)
{
    header('Content-Type: text/plain; charset=utf-8');
    echo $data;
}


# 토렌트 rss 요청을 처리한다.
function make_rss()
{
    $board_urls = parse_param();

    $confirmed_urls = update_domain($board_urls);  # 도메인 변경 처리

    $torrent_items = get_items($confirmed_urls);

    $rss = build_rss($torrent_items);

    magnet_cache_control('Write');  # 마그넷 캐시 디스크 기록

    response($rss);
}


# 토렌트 파일(.torrent)을 다운로드한다.
function download_torrent($link)
{
    global $logger;

    $logger->debug("Downloading torrent file from $link");

    list($http_code, $header, $body) = curl_fetch($link);

    if ($http_code != 200) {
        $logger->error("Error. Download torrent file from $link returned http_code $http_code");
        return;
    }

    if (preg_match('/^Content-Disposition:.+$/mi', $header, $match)) {
        header("Content-Type: application/x-bittorrent");
        header(trim($match[0]));
        echo $body;
    }
}


# 웹페이지에서 토렌트 파일 링크를 찾아 다운로드한다.
function do_download($url)
{
    global $logger;

    $replace_patterns = $GLOBALS['CONFIG']['_down_link_preprocess'];
    list($http_code, $header, $html) = curl_fetch($url);

    if ($http_code != 200) {
        $logger->error("Error. Fetch from $url returned http_code $http_code");
        $logger->error($header);
        return;
    }

    $html = preprocess($html, $replace_patterns);  # html 내용의 일부를 미리 변경
    $pattern = $GLOBALS['CONFIG']['_down_link'];

    if (preg_match($pattern, $html, $match)) {
        $logger->debug($match);
        $link = url_join($url, $match[1]);
        download_torrent($link);
    }
}


function main()
{
    global $logger;

    # 요청 정보 출력
    $logger->info("torr 버전: " . VERSION);
    $logger->info("새로운 요청이 들어왔습니다. IP=" .  $_SERVER['REMOTE_ADDR']);
    $logger->info("REQUEST_URI: " . $_SERVER['REQUEST_URI']);
    $logger->debug($_SERVER);

    if (isset($_GET['d'])) {
        do_download(base64_decode($_GET['d']));
    } else {
        make_rss();
        self_update();
    }
}


function gen_base64() {
    $local_base64 = base64_encode(file_get_contents(__FILE__));
    print($local_base64);
}


#################################################################
## Main()
#################################################################
if (isset($argc) && $argc > 1 && $argv[1] == 'base64') {
    gen_base64();
} else {
    main();
}

