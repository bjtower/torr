# It's torr! v20230105

공개 토렌트 사이트의 게시물을 RSS 형태로 변환
- 해당 It's torr 는 grollcake-torr님의 torr 를 기반으로 제작을 하였습니다.
 
  grollcake-torr 님의 부재로 해당 torr가 업데이트가 5개월이 넘도록 업데이트가 미흡하여 링크를 수정 후 제작하였으며,
 
  본 프로젝트는 문제가 발생할 시 자진 삭제할 예정입니다.

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

 *   20230105 - 토렌트 사이트 변경
 *   20230101 - 토렌트 사이트 변경
 *   20221223 - 토렌트 사이트 변경
 *   20221215 - 토렌트 사이트 변경
 *   20221211 - 토렌트 사이트 변경
 *   20221207 - 토렌트 사이트 변경
 *   20221126 - 토렌트 사이트 변경
 *   20221120 - 토렌트 사이트 변경
 *   20221111 - 토렌트 사이트 변경
 *   20221106 - 토렌트 사이트 변경
 *   20221024 - 토렌트 사이트 변경
 *   20221014 - 토렌트 사이트 변경
 *   20221011 - 토렌트 사이트 변경
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
 *   20170613 - 사이트 정보 업데이트 태그(code > pre) 변경. html의 선처리 추가
