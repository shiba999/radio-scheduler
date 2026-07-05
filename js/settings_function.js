// フォームで使用する関数 > マルチバイトが含まれていないか確認する関数
// 一部禁止な１バイト記号あり

export function validate_input(value) {

	// 1) マルチバイト（ASCII以外）チェック
	// ASCIIは 0x00〜0x7F、それ以外が含まれていたらNG

	const has_multibyte = /[^\x00-\x7F]/.test(value);

	// 2) 禁止したい文字チェック
	//    : ; \ ^ = - ? # " ' !
	//    \ や " ' などは正規表現内でエスケープが必要

	const forbidden_pattern = /[:;\\^=?#"\'!]/;
	//const forbidden_pattern = /[@:;\\^=\-?#"'!]/;
	const has_forbidden = forbidden_pattern.test(value);

	return {
		result: ! has_multibyte && ! has_forbidden,
		has_multibyte,
		has_forbidden,
	};

}

// 有効なメールアドレスか否か確認する関数

export function validate_email(email) {
	const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
	return pattern.test(email);
}

