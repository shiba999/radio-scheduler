import * as fnc from "../js/function.js";
import * as s_fnc from "../js/settings_function.js";

// * * * * * * * * * * * * * * * * * * * * * *
// フォーム関係の要素
// * * * * * * * * * * * * * * * * * * * * * *

const set_mail = document.getElementById("sendmail_form");
const sm_address = document.getElementById("email_address");
const sm_from = document.getElementById("from_mail");
const sm_from_name = document.getElementById("from_name");
const phpm_host = document.getElementById("phpm_host");
const phpm_auth = document.getElementById("phpm_auth");
const phpm_user = document.getElementById("phpm_user");
const phpm_pass = document.getElementById("phpm_pass");
const phpm_port = document.getElementById("phpm_port");
const e_gmail_reurl = document.getElementById("gmail_redirect_url");
const gapi_cid = document.getElementById("gapi_cid");
const gapi_cs = document.getElementById("gapi_cs");
const sm_message = document.getElementById("sendmail_message");
const setting_mail_set = document.getElementById("setting_mail_set");
const send_test = document.getElementById("send_test");

// ↓の情報取得までフォームを触れないようにする

set_mail.style.pointerEvents = "none";
set_mail.style.opacity = "0.38";

// Email method 選択によって表示を切り替えを行う関数

// 表示・非表示を行う要素群

const e_mbsm = document.getElementsByClassName("sendmail_mbsm");
const e_phpm = document.getElementsByClassName("sendmail_phpm");
const e_gapi = document.getElementsByClassName("sendmail_gapi");

function sm_display_switch(type) {

	//console.log(e_phpm);

	// 一旦すべて非表示にする

	for ( let n = 0; n < e_mbsm.length; n++ ) {
		e_mbsm[n].style.display = "none";
	}

	for ( let n = 0; n < e_phpm.length; n++ ) {
		e_phpm[n].style.display = "none";
	}

	for ( let n = 0; n < e_gapi.length; n++ ) {
		e_gapi[n].style.display = "none";
	}

	// 必要な要素のみ表示・非表示

	console.log(type);

	let this_elements;

	if ( type === "mbsm" || type === "no" ) {
		this_elements = e_mbsm;
		sm_message.style.display = "none";
	} else if ( type === "phpm" ) {
		this_elements = e_phpm;
	} else if ( type === "gapi" ) {
		this_elements = e_gapi;
	}

	// テスト送信ボタンの表示・非表示

	if ( type === "no" ) {
		send_test.style.display = "none";
	} else {
		send_test.style.display = "inline";
	}

	//console.log(this_elements);

	for ( let n = 0; n < this_elements.length; n++ ) {
		this_elements[n].style.display = "block";
	}

}

// * * * * * * * * * * * * * * * * * * * * * *
// 現在保存中の設定情報を取得してフォーム項目に反映
// * * * * * * * * * * * * * * * * * * * * * *

async function get_sendmail_values() {

	const send_params_title = {
		file: "sendmail"
	};

	const query_string_title = new URLSearchParams(send_params_title).toString();
	const sendmail_object = await fnc.fetch_template("../php/setting_get.php", query_string_title);

	//console.log(sendmail_object);

	if (sendmail_object.sm_address) {
		sm_address.value = sendmail_object.sm_address;
	}

	if (sendmail_object.sm_from) {
		sm_from.value = sendmail_object.sm_from;
	}

	if (sendmail_object.sm_from_name) {
		sm_from_name.value = sendmail_object.sm_from_name;
	}

	let this_method = "";

	if ( sendmail_object.sm_method != "" ) {
		this_method = sendmail_object.sm_method
		document.querySelector('input[name="email_method"][value="' + sendmail_object.sm_method + '"]').checked = true;
	} else {
		this_method = "mbsm";
	}

	sm_display_switch(this_method);

	if (sendmail_object.phpm_host) {
		phpm_host.value = sendmail_object.phpm_host;
	}

	if (sendmail_object.phpm_auth === true) {
		phpm_auth.checked = true;
	}

	if (sendmail_object.phpm_user) {
		phpm_user.value = sendmail_object.phpm_user;
	}

	if (sendmail_object.phpm_pass) {
		phpm_pass.value = sendmail_object.phpm_pass;
	}

	if (sendmail_object.phpm_secu) {
		document.querySelector('input[name="phpm_secu"][value="' + sendmail_object.phpm_secu + '"]').checked = true;
	}

	if (sendmail_object.phpm_port) {
		phpm_port.value = sendmail_object.phpm_port;
	}

	if (sendmail_object.gapi_cid) {
		gapi_cid.value = sendmail_object.gapi_cid;
	}

	if (sendmail_object.gapi_cs) {
		gapi_cs.value = sendmail_object.gapi_cs;
	}

	// ここで Gmail API の案内用リダイレクトURLを出力

	const current_url = window.location.href;// このページ
	const current_url_array = current_url.split("/");// 一度 / で分割
	current_url_array.splice(-2);// https://***/***/page/settings.html 後ろの2個削除 ( page と settings.html )
	const gmail_redirect_url = current_url_array.join("/") + "/php/gmail_oauth_setup.php";

	e_gmail_reurl.innerHTML = gmail_redirect_url;

	//console.log(gmail_redirect_url);

	// 情報取得完了したらフォームを触れるように復旧

	set_mail.style.pointerEvents = null;
	set_mail.style.opacity = null;

}

get_sendmail_values();

// フォーム内 Email method を変更時に表示切替

const e_method_type = document.getElementsByName("email_method");

for( let i = 0; i < e_method_type.length; i++ ) {
	e_method_type[i].onclick = function() {
		sm_display_switch(e_method_type[i].value);
	}
}

// * * * * * * * * * * * * * * * * * * * * * *
// 設定項目を保存する関数
// * * * * * * * * * * * * * * * * * * * * * *

setting_mail_set.addEventListener("click", async function(event) {

	const sm_address_value = sm_address.value;
	const sm_from_value = sm_from.value;
	const sm_from_name_value = sm_from_name.value;
    const sm_method_value = document.querySelector('input[name="email_method"]:checked').value;
	const phpm_host_value = phpm_host.value;
	const phpm_auth_value = phpm_auth.checked;
	const phpm_user_value = phpm_user.value;
	const phpm_pass_value = phpm_pass.value;
	const phpm_secu_value = document.querySelector('input[name="phpm_secu"]:checked').value;
	const phpm_port_value = phpm_port.value;
	const gapi_cid_value = gapi_cid.value;
	const gapi_cs_value = gapi_cs.value;

	//console.log(phpm_auth_value);

    // マルチバイト混入チェック

	const value_array_mb = new Array(phpm_host_value, phpm_user_value, phpm_pass_value, gapi_cid_value, gapi_cs_value);

    // 有効メールアドレスチェック

	const value_array_mail = new Array(sm_address_value, sm_from_value);

	//console.log(value_array_mb);
	//console.log(value_array_mail);

	// 入力値のチェック (マルチバイト混入)

	let false_count = 0
	let false_value_array = new Array();

	for ( let n = 0; n < value_array_mb.length; n++ ) {

		const this_result = s_fnc.validate_input(value_array_mb[n]);

		console.log(this_result);

		if ( this_result.result === false ) {
			//console.log(value_array_mb[n]);
			false_value_array.push(value_array_mb[n]);
			false_count++;
		}

	}

	// 入力値のチェック (メールアドレスチェック)

	for ( let n = 0; n < value_array_mail.length; n++ ) {

		const this_result = s_fnc.validate_email(value_array_mail[n]);

		console.log(this_result);

		if ( this_result.result === false ) {
			//console.log(value_array_mail[n]);
			false_value_array.push(value_array_mail[n]);
			false_count++;
		}

	}

	console.log(false_count);

	if ( false_count === 0 ) {

		// 保存処理へ

		const send_object = {
			sm_address: sm_address_value,
			sm_from: sm_from_value,
			sm_from_name: sm_from_name_value,
			sm_method: sm_method_value,
			phpm_host: phpm_host_value,
			phpm_auth: phpm_auth_value,
			phpm_user: phpm_user_value,
			phpm_pass: phpm_pass_value,
			phpm_secu: phpm_secu_value,
			phpm_port: phpm_port_value,
			gapi_cid: gapi_cid_value,
			gapi_cs: gapi_cs_value
		};

		const send_json = JSON.stringify(send_object);

		const send_params = {
			file: "sendmail",
			json: send_json
		};

		//console.log(send_params);

		const query_string = new URLSearchParams(send_params).toString();
		const save_result = await fnc.fetch_template( "../php/setting_set.php", query_string );

		//console.log(save_result);

		if ( save_result > 0 ) {
			sm_message.innerHTML = "設定を保存しました。";
			get_sendmail_values();
		} else {
			sm_message.innerHTML = "設定の保存に失敗しました。";
		}

	} else {

		sm_message.innerHTML = "不正な文字列が含まれています。" + false_value_array.join(", ");

	}

});

// * * * * * * * * * * * * * * * * * * * * * *
// テストメール送信ボタン
// * * * * * * * * * * * * * * * * * * * * * *

send_test.addEventListener("click", async function(event) {

	sm_message.innerHTML = "現在の設定からテストメールの送信を試みます。";

	const result = await fnc.fetch_template("../php/send_mail_fetch.php");

	console.log(result);

	sm_message.innerHTML = result["message"];

});

