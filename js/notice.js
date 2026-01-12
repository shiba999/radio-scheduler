
	// メンテナンス情報の読み込み

	export async function maintenance_information(arg_var, arg_func) {

		//console.log("地域ID: " + region);

		const maintenance_params = new URLSearchParams({
			area: arg_var.v.region
		});

		const fetch_string = new URLSearchParams(maintenance_params).toString();
		const maintenance_object = await arg_func.fetch_template("./php/maintenance_xml_analysis.php", fetch_string);

		//console.log(maintenance_object);

		let html = "";

		if ( maintenance_object.info.length > 0 ) {

			const info_object = maintenance_object.info;

			for ( let n = 0; n < info_object.length; n++ ) {

				html += '<div class="info_set">';
				html += '<p class="info_title">' + info_object[n]["title"] + '</p>';
				html += '<p class="info_body">' + info_object[n]["body"] + '</p>';
				html += '</div>';

			}

		}

		// 整形したお知らせ情報を表示

		arg_var.e.xml_info.innerHTML = html;

		// クリックでお知らせ項目を開閉させるイベントを登録

		document.addEventListener("click", async function(event) {

			//console.log(event);

			if ( event.target.classList.contains("info_set") ) {

				console.log("info_set");

				// クリック .info_set の子要素を取得

				const child_nodes = event.target.children;

				// 子要素の .info_body を探す

				for ( let n = 0; n < child_nodes.length; n++ ) {

					const child_node = child_nodes[n];

					if ( child_node.classList.contains("info_body") ) {
						child_node.classList.toggle("is_open");
					}

				}

			}

		});

	}
