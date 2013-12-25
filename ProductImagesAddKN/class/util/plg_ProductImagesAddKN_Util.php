<?php
/*
 * ProductImagesAddKN
 *
 * Copyright(c) kaoken All Rights Reserved.
 *
 * http://www.kaoken.net/
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 * 
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */


/**
 * plg_ProductImagesAddKN_Util
 * 
 * @package   
 * @author 
 * @copyright gencyolcu
 * @version 2013
 * @access public
 */
class plg_ProductImagesAddKN_Util
{
	/** @var object 自分自身のインタンスを保持 */
	private static $ms_My = null;
	const PLUGIN_NAME = 'ProductImagesAddKN';


	/**
	 * コンストラクタ
	 *
	 * @return void
	 */
	public function __construct(){}
	
	
	/**
	 * このクラスのインスタンスを取得する
	 *
	 * @return object このクラスのインスタンス
	 */
	public function GetMy()
	{
		if (is_null(self::$ms_My)) {
			self::$ms_My = new self;
		}

		return self::$ms_My;
	}
	public function GetPluginName(){return self::PLUGIN_NAME;}
	/**
	 * 実行場所のURL取得
	 * 
	 * @return string
	 */
    public function GetFullUrl()
	{
		$https = !empty($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], 'on') === 0;
		return
			($https ? 'https://' : 'http://').
			(!empty($_SERVER['REMOTE_USER']) ? $_SERVER['REMOTE_USER'].'@' : '').
			(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ($_SERVER['SERVER_NAME'].
			($https && $_SERVER['SERVER_PORT'] === 443 ||
			$_SERVER['SERVER_PORT'] === 80 ? '' : ':'.$_SERVER['SERVER_PORT']))).
			substr($_SERVER['SCRIPT_NAME'],0, strrpos($_SERVER['SCRIPT_NAME'], '/'));
    }	
	/**
	 * MIMEから拡張子を取得
	 * 
	 * @param string $mime	MIME文字列
	 * @return string
	 */
	public function GetExtFromMIME($mime)
	{
		static $asMIME = array( 
			'text/plain'=>'txt', 
			'text/html'=>'htm', 
			'image/jpeg'=>'jpg',
			'image/gif'=>'gif',
			'image/png'=>'png',
			'image/x-bmp'=>'bmp',
			'application/postscript'=>'ai',
			'image/x-photoshop'=>'psd',
			'application/postscript'=>'eps',
			'application/pdf'=>'pdf',
			'application/x-shockwave-flash'=>'swf',
			'application/x-lha-compressed'=>'lzh',
			'application/x-zip-compressed'=>'zip',
		);
		return $asMIME[$mime];
	}

	/**
	 * 32ビット符号付き整数をオーバーフローしないために
	 *
	 * @param int $size 
	 * @return int
	 */
	public function FixIntegerOverflow($size)
	{
		if($size < 0)
			$size += 2.0 * (PHP_INT_MAX + 1);
		return $size;
	}
	
	/**
	 * 304 NotModifiedチェック
	 * 
	 * @param int	$lastModifiedTime integer 型の Unix タイムスタンプです
	 * @param string $seedEtag		   Etagの種となる値
	 * @return bool
	 */
	public function NotModifiedHeders($lastModifiedTime, $seedEtag)
	{		
		$etag = md5($seedEtag);
		
		// headers
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT\n\n");
		header("Last-Modified: ".gmdate("D, d M Y H:i:s", $lastModifiedTime)." GMT"); 
		header("Etag: {$etag}"); 
		if (@strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $lastModifiedTime || 
			@trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag) { 
			header("HTTP/1.1 304 Not Modified");
			return true;
		} 
		return false;
	}
	
	/**
	 * プラグイン内で使用するDBセットを読み込む
	 * 
	 * @param string $name DBセット名
	 * @return instance
	 */
	public function GetDB($name)
	{
		static $saClass = array();
		
		$className = "plg_".self::PLUGIN_NAME."_DB_".$name;
		if( array_key_exists($className, $saClass) )
		{
			return $saClass[$className];	
		}
		if(!class_exists($className)){
			$path = $this->GetPUploadPath('/class/db',"DB_".$name.'.php');
			if( is_file( $path ) )
				require_once $path;
		}
		
		$saClass[$className] = new $className();
		return $saClass[$className];
	}
	
	
	
	
	
	//###########################################################
	//##
	//## ファイル＆ディレクトリ、コピー＆作成＆移動など
	//##
	//###########################################################
	/**
	 * このプラグイン内にディレクトリ作成
	 * 
	 * @param string $dirName
	 * @param mixed $dst
	 * @return boolean 成功した場合はtrueを返す
	 */
	public function DirCreatedToHTML($dirName)
	{
		$dirPath = PLUGIN_HTML_REALDIR . self::PLUGIN_NAME."/".$dirName;
		if( @mkdir($dirPath,0777) === false )
		{
			SC_Utils_Ex::sfDispSiteError(FREE_ERROR_MSG, '', false, PLUGIN_HTML_REALDIR . self::PLUGIN_NAME . "' 内にディレクトリ'{$dirName}'を作成できませんでした。パーミッションをご確認ください。");
			return false;
		}
		return true;
	}
	
	/**
	 * プラグインのアップロードディレクトリからHTMLディレクトリへファイルをコピーする
	 * 
	 * @param string $srcFile
	 * @param string $dstFile
	 * @return boolean 成功した場合はtrueを返す
	 */
	public function CopyToHTMLFileFromUploadFile($srcFile, $dstFile)
	{
		$plginUR = PLUGIN_UPLOAD_REALDIR . self::PLUGIN_NAME."/";
		$plginHR = PLUGIN_HTML_REALDIR . self::PLUGIN_NAME."/";
		// ファイルコピー
		if( @copy($plginUR . $srcFile, $plginHR. $dstFile) === false )
		{
			SC_Utils_Ex::sfDispSiteError(FREE_ERROR_MSG, '"', false, $plginHR . '" に"'.$dstFile.'"が書き込めません。パーミッションをご確認ください。');
			return false;
		}
		return true;
	}
	
	/**
	 * ディレクトリごとコピーする
	 * 
	 * @param string $src 元となる場所
	 * @param string $dst コピー先
	 * @return boolean
	 */
	public function CopyRecursive($src, $dst)
	{
		if (is_dir($src))
		{
			if( @mkdir($dst,0777) === false )
			{
				SC_Utils_Ex::sfDispSiteError(FREE_ERROR_MSG, '', false, $dst . "' に書き込めません。パーミッションをご確認ください。");
				return false;
			}
			
			$files = scandir($src);
			foreach ($files as $file)
			{
				if (($file != ".") && ($file != ".."))
				{
					if( self::CopyRecursive("$src/$file", "$dst/$file") === false )
						return false;
				}
			}
		}
		else if (file_exists($src)) {
			if( @copy($src, $dst) === false )
			{
				SC_Utils_Ex::sfDispSiteError(FREE_ERROR_MSG, '', false, $dst . "' に書き込めません。パーミッションをご確認ください。");
				return false;
			}
		}
		return true;
	}
	
	/**
	 * プラグインのアップロードDIRからHTMLディレクトリへディレクトリ毎コピーする
	 * 
	 * @param string $src 元となる場所
	 * @param string $dst コピー先
	 * @return boolean
	 */
	public function CopyToHTMLDirFromUploadDir($src, $dst)
	{
		$plginUR = PLUGIN_UPLOAD_REALDIR . self::PLUGIN_NAME;
		$plginHR = PLUGIN_HTML_REALDIR . self::PLUGIN_NAME;
		return $this->CopyRecursive($plginUR.$src, $plginHR.$dst);
	}
	
	/**
	 * キャッシュディレクトリまたは一時キャッシュディレクトリ内の画像ファイルを
	 * 指定した数ごとたくわえ、指定インスタンスのメンバ関数を呼び出し、処理させる。
	 * 
	 * @param mix      $val          $funcに渡す値
	 * @param instance $ins          インスタンス
	 * @param staring  $func         インスタンスのメンバ関数名
	 * @param int      $storeNum     蓄えるファイル名の数
	 * @param boolean  $isCashImgDir 検索対象はがcash_imageディレクトリの場合true、そうでない場合はtmp_imageディレクトリ
	 * @return boolean タイムアウトした場合falseを返す
	 */
	public function ReadCashImgFileNames(&$val,&$ins,$func,$storeNum=100,$isCashImgDir=true)
	{
		$dir = $isCashImgDir?$this->GetHtmlDirPath('/cash_images'):$this->GetHtmlDirPath('/tmp_images');

		$timeOut = floatval(ini_get('max_execution_time'));
		if( $timeOut === 0.0 )
			$timeOut = 30.0;
		else
		{
			if( $timeOut !== 0.0 )
				$timeOut -= 10.0;
			if( $timeOut <= 0.0 )
				$timeOut += 8.0;
		}
		$cnt=0;
		$stCnt=0;
		$aFileName = array();
		$isOK = true;
		if(($handle = opendir($dir)))
		{
			// 取得する文字数を少なくするために、カレントディレクトリを変更
			$cDir = getcwd();
			chdir($dir);
			// 開始時刻取得
			$startT = microtime(true);
			while($file = readdir($handle))
			{
				if( "." == $file || ".." == $file )continue;
				if (is_file($file)) {
					$aFileName[] = $file;
					++$stCnt;
					if( $storeNum <= $stCnt )
					{
						$stCnt = 0;
						$isOK = $ins->$func($val, $dir, $aFileName);
						if( !$isOK ){
							chdir($cDir);
							closedir($handle);
							GC_Utils_Ex::gfPrintLog("ファイル{$cnt}個読み込み後、強制停止しました。");
							return false;
						}
						$aFileName = array();
					}
					if( (++$cnt%5000) === 0 )
					{
						if( (microtime(true) - $startT) > $timeOut )
						{
							chdir($cDir);
							closedir($handle);
							GC_Utils_Ex::gfPrintLog("ファイル{$cnt}個の読み込みに{$timeOut}秒以上かかりました。強制的に読み込みを停止します。");
							return false;// time out
						}
					}
				}
			}
			if( $stCnt !== 0 && $isOK )
			{
				$ins->$func($val, $dir, $aFileName);
			}
			chdir($cDir);
			closedir($handle);
		}
		return true;
	}
	
	/**
	 * キャッシュ画像ディレクトリ内の指定パターンにマッチするパス名を探し削除する
	 * ファイル数少ないことがわかっている場合、こいつを使う
	 * 
	 * @param string   $pattern  パターンにマッチするパス名
	 * @param boolean  $isCashImgDir 検索対象はがcash_imageディレクトリの場合true、そうでない場合はtmp_imageディレクトリ
	 * @return void
	 */
	public function GlobDeleteCashImages($pattern,$isCashImgDir=true)
	{
		$dir = $isCashImgDir?$this->GetHtmlDirPath('/cash_images'):$this->GetHtmlDirPath('/tmp_images');
		// 取得する文字数を少なくするために、カレントディレクトリを変更
		$cDir = getcwd();
		chdir($dir);
		$aDelFile = glob($pattern, GLOB_NOCHECK|GLOB_NOSORT);
		if( is_array($aDelFile) )
		{
			if( count($aDelFile) > 0 )
				foreach($aDelFile as &$val)@unlink($val);
		}
		chdir($cDir);
	}
	/**
	 * 指定したディレクトリのファイルサイズを取得する
	 * 
	 * @param string   $dir  ディレクトリパス
	 * @return array
	 */
	public function GetDirFileSize($dir)
	{
		$aRet = array('size'=>0,'num'=>0);
		
		// shell_execが使えるなら使う
		// こっちの方が早いので
		if( function_exists('shell_exec') )
		{
			$isErr = false;
			if(strtoupper(PHP_OS) === 'LINUX')
			{
				// ファイルサイズ取得
				$strTmp = shell_exec('(echo a=0; find '.$dir.' -type f -printf "a+=%s\n"; echo a) | bc');
				if( !is_null($strTmp) )
					$aRet['size'] = $this->GetFileSizePrefix($this->FixIntegerOverflow(intval($strTmp)));
				else $isErr=true;
				// ファイル数取得
				$strTmp = $strChasSize = shell_exec("find {$dir} -type f | wc -l");
				if( !is_null($strTmp) && !$isErr )
					$aRet['num'] = intval($strTmp);
				else $isErr=true;
			}
			if( !$isErr )return $aRet;
		}
		
		// 上記がうまく動作しない場合、しかたなくこいつを使う
		$handle = opendir($dir);
		while ($file = readdir($handle)) {
			if ($file != '..' && $file != '.' && !is_dir($dir.'/'.$file))
			{
				$aRet['size'] += filesize($dir.'/'.$file);
				++$aRet['num'];
			}
			else if(is_dir($dir.'/'.$file) && $file != '..' && $file != '.')
			{
				$aTmp += GetDirSize($dir.'/'.$file);
				$aRet['size'] += $aTmp['size'];
				$aRet['num'] += $aTmp['num'];
			}
		}
		return $aRet;
	}
	//###########################################################
	//##
	//## ファイルパス関係
	//##
	//###########################################################
	/**
	 * アップロード・プラグイン内のディレクトリパスを作る
	 * 
	 * @param string $dir  ディレクトリ名 例："/hoge/hoge"
	 * @return string
	 */
	public function GetUploadDirPath($dir)
	{
		return PLUGIN_UPLOAD_REALDIR . self::PLUGIN_NAME."{$dir}/";
	}
	/**
	 * HTML・プラグイン内のディレクトリパスを作る
	 * 
	 * @param string $dir  ディレクトリ名 例："/hoge/hoge"
	 * @return string
	 */
	public function GetHtmlDirPath($dir)
	{
		return PLUGIN_HTML_REALDIR . self::PLUGIN_NAME."{$dir}/";
	}

	/**
	 * アップロード・プラグイン内のファイルパスを作る
	 * 
	 * @param string $dir  ディレクトリ名 例："/hoge/hoge"
	 * @param string $file ファイル名
	 * @return string
	 */
	public function GetPUploadPath($dir,$file)
	{
		return $this->GetUploadDirPath($dir)."/plg_".self::PLUGIN_NAME."_{$file}";
	}
	
	/**
	 * HTML・プラグイン内の有効後のファイルパスを作る
	 * 
	 * @param string $dir  ディレクトリ名 例："/hoge/hoge"
	 * @param string $file ファイル名
	 * @return string
	 */
	public function GetPHtmlPath($dir,$file)
	{
		return $this->GetHtmlDirPath($dir)."/{$file}";
	}
	
	/**
	 * アップロード・プラグイン内のテンプレートパスを返す
	 * 
	 * @param string $name DBセット名
	 * @return string
	 */
	public function GetTemplatePath($dir, $name)
	{
		return $this->GetPUploadPath('/templates/'.$dir, $name.'.tpl');
	}
	
	/**
	 * HTML・キャッシュ画像を保存しているファイルパスを返す
	 * 
	 * @param string $fileName 画像ファイル名
	 * @return string
	 */
	public function GetCashImagesPath($fileName)
	{
		return $this->GetHtmlDirPath('/cash_images').$fileName;
	}
	
	/**
	 * HTML・一時キャッシュ画像を保存しているファイルパスを返す
	 * 
	 * @param string $fileName 画像ファイル名
	 * @return string
	 */
	public function GetTmpCashImagesPath($fileName)
	{
		return $this->GetHtmlDirPath('/tmp_images').$fileName;
	}
	//###########################################################
	//##
	//## ファイル処理関係
	//##
	//###########################################################
	/**
	 * ProductImagesAddKNプラグイン内の有効後のファイル名先に内容を追加書き込みする。
	 * 主にデバッグ的な用途で使う
	 * 
	 * @param string $file ファイル名
	 * @param string $mix  書き込みたい内容
	 * @return void
	 */
	public function OutputFile($file, $mix)
	{
		$file = $this->GetPHtmlPath('', $file);
		$fh=fopen($file,"a");
		fwrite($fh,print_r($mix,true));
		fclose($fh);
	}
	
	/**
	 * ファイルサイズの接頭語を返す
	 * 
	 * @param int $byteSize  バイト単位のファイルサイズ
	 * @return string
	 */
	public function GetFileSizePrefix($byteSize)
	{
		$byteSize = $this->FixIntegerOverflow($byteSize);
		if( $byteSize > 0 )
		{
			$tmp = $byteSize;
			for( $i=0; $tmp >= 1024; $i++ )
			{
				$tmp /= 1024.0;	
			}

			$tmp = round($tmp,1);
			switch($i)
			{
				case 0: $tmp .= " B"; break;
				case 1: $tmp .= " KB"; break;
				case 2: $tmp .= " MB"; break;
				case 3: $tmp .= " GB"; break;
				case 4: $tmp .= " TB"; break;
				case 5: $tmp .= " PB"; break;
				case 6: $tmp .= " EB"; break;
				case 7: $tmp .= " AB"; break;
				case 8: $tmp .= " YB"; break;
				default: $tmp .= " ?B";
			}
			return $tmp;
		}
		return " 0 byte";
	}
	
	
	/**
	 * 一時ファイルのロックファイルを作成する
	 * 
	 * @param staring $name  一時ロックファイル名
	 * @param boolean $isEX  trueの場合、排他ロック。falseの場合共有ロック
	 * @return mixi ロックできた場合はオブジェクトを返す
	 */
	public function TmpFileLock($name, $isEX)
	{
		$file = new stdClass();
		$file->path = $this->GetPHtmlPath('/tmp', $name);
		$file->handle = fopen($file->path, "w+");
		if(flock($file->handle, $isEX?LOCK_EX:LOCK_SH)){	//排他的ロック
			return $file;
		}
		 
		return null;
	}
	/**
	 * 一時ファイルのロックを解除
	 * 
	 * @param object $file  一時ロックファイル名
	 * @return void
	 */
	public function TmpFileUnlock(&$file)
	{
		fclose($file->handle);
		// タイミングが合えば削除されるでしょう（削除は重要ではないので！
		@unlink($file->path);
		$file = null;
	}
}

?>