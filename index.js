
var posturl = "";

function showToast(_message) {
    var myToastEl = document.querySelector('#toast');
    var myToast = bootstrap.Toast.getOrCreateInstance(myToastEl)
    document.querySelector('#toast_message').innerHTML = _message;
    myToast.show();
}

function checkinout(dom) {
    console.log(dom.checked);
    if (dom.checked == true) {
        if (!checkin(dom)) {
            dom.checked = false;
        } else {
            localStorage.setItem('labable_checkinout_checkbox', 1);
        }
    }
    else {
        if (!checkout(dom)) {
            dom.checked = true;
        }
        else {
            localStorage.setItem('labable_checkinout_checkbox', 0);
        }
    }
    console.log(localStorage.getItem('labable_checkinout_checkbox'));
}
function checkin(dom) {
    const name = document.querySelector('#name').value;
    const temp = document.querySelector('#temp').value;

    if (!name || !temp) {
        alert("名前、体温を記入してください");
        return false;
    }

    if (!document.querySelector('#agree_checkbox').checked) {
        alert("注意事項に同意してください");
        return false;
    }


    const icon = document.querySelector('#icon').value;
    const channel = document.querySelector('#channel').value;
    const message = document.querySelector('#message').value;
    const how_to_move = document.querySelector('#select_how_to_move').options[document.querySelector('#select_how_to_move').selectedIndex].innerHTML;

    if (37.5 <= temp) {
        alert("無理をせずお家で休みましょう");
        return false;
    }

    const obj = {
        name: name,
        channel: channel,
        icon_emoji: icon,
        text: `*IN @ ${temp} [${how_to_move}]* ${message}`,
        posturl: posturl,
        inout: 'in'
    };

    const method = "POST";
    const body = JSON.stringify(obj);
    const headers = {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
    };
    fetch("./slack.php", { method, headers, body })
        .then(response => {
            return response.json();
        })
        .then(res => {
            showToast(res.message);
            let users = JSON.parse(res.users);
            if( users.length > 0 ){
                document.querySelector('#area_onsite_users').hidden = false;
                 // すでにあるspan要素をすべて削除する
                 let spans = document.querySelectorAll('#area_onsite_users span');
                 for( span of spans){
                     span.remove();
                 }
                for (user of users) {
                    let span = document.createElement('span');
                    span.classList = "badge rounded-pill bg-success";
                    span.innerHTML = user;
                    document.querySelector('#area_onsite_users').appendChild(span);
                    document.querySelector('#area_onsite_users').hidden = false;
                }
            }
            else{
                document.querySelector('#area_onsite_users').hidden = true;
            }
        })
        .catch(console.error);

    dom.disabled = true;
    setTimeout(function () {
        dom.disabled = false;
    }, 1000);
    return true;
}

function is_json(data) {
    try {
        JSON.parse(data);
    } catch (error) {
        return false;
    }
    return true;
}

function checkout(dom) {




    const name = document.querySelector('#name').value;
    const temp = document.querySelector('#temp').value;
    const icon = document.querySelector('#icon').value;
    const channel = document.querySelector('#channel').value;
    const message = document.querySelector('#message').value;
    const how_to_move = document.querySelector('#select_how_to_move').options[document.querySelector('#select_how_to_move').selectedIndex].innerHTML;


    if (!name || !temp) {
        alert("名前、体温を記入してください");
        return false;
    }

    if (!document.querySelector('#agree_checkbox').checked) {
        alert("注意事項に同意してください");
        return false;
    }


    const obj = {
        name: name,
        channel: channel,
        icon_emoji: icon,
        text: `*OUT [${how_to_move}]* ${message}`,
        posturl: posturl,
        inout: 'out'
    };
    const method = "POST";
    const body = JSON.stringify(obj);
    const headers = {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
    };
    fetch("./slack.php", { method, headers, body })
        .then(response => response.json()) // 帰ってきた値をjsonにして次に渡す
        .then(res => {
            showToast(res.message);
            let users = JSON.parse(res.users);
            if( users.length > 0 ){
                document.querySelector('#area_onsite_users').hidden = false;
                // すでにあるspan要素をすべて削除する
                let spans = document.querySelectorAll('#area_onsite_users span');
                for( span of spans){
                    span.remove();
                }

                for (user of users) {
                    let span = document.createElement('span');
                    span.classList = "badge rounded-pill bg-success";
                    span.innerHTML = user;
                    document.querySelector('#area_onsite_users').appendChild(span);
                    document.querySelector('#area_onsite_users').hidden = false;
                }
            }
            else{
                document.querySelector('#area_onsite_users').hidden = true;
            }
        })
        .catch(console.error);

    dom.disabled = true;
    setTimeout(function () {
        dom.disabled = false;
    }, 1000);
    return true;
}

function saveText(obj) {
    if (obj.id == 'temp') {
        if (obj.value <= 35.4 || obj.value >= 37.5) {
            alert('入構申請できる体温は35.5〜37.4℃です。');
            obj.value = 36.8;
            document.querySelector('#range_temp').value = 36.8;
        }
    }
    localStorage.setItem(`labable_${obj.id}`, obj.value);
}

function getInMembers() {
    // test
    const name = document.querySelector('#name').value;
    const channel = document.querySelector('#channel').value;

    const obj = {
        name: name,
        channel: channel,
        posturl: posturl
    };

    const method = "POST";
    const body = JSON.stringify(obj);
    const headers = {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
    };
    fetch("./getInMembers.php", { method, headers, body })
        .then(response => {
            return response.json();
        })
        .then(res => {
            let users = JSON.parse(res.users);
            if( users.length > 0 ){
                document.querySelector('#area_onsite_users').hidden = false;
                 // すでにあるspan要素をすべて削除する
                 let spans = document.querySelectorAll('#area_onsite_users span');
                 for( span of spans){
                     span.remove();
                 }
                for (user of users) {
                    let span = document.createElement('span');
                    span.classList = "badge bg-success me-2";
                    span.innerHTML = user;
                    document.querySelector('#area_onsite_users').appendChild(span);
                    document.querySelector('#area_onsite_users').hidden = false;
                }
            }
            else{
                document.querySelector('#area_onsite_users').hidden = true;
            }
            
        })
        .catch(console.error);
}

window.onload = function () {

    // URLを取得
    let url = new URL(window.location.href);

    // URLSearchParamsオブジェクトを取得
    let params = url.searchParams;

    // getメソッド
    if (!params.get('posturl') || !params.get('ch')) {
        alert('Invalid URL：無効なリンクです。リンク配布者に確認をするか、アクセスリンク作成ページから正しいリンクを作成してください');
        window.location.href = "./getStart.html";
        return;
    }
    posturl = params.get('posturl');
    //console.log(posturl);

    document.querySelector('#name').value = localStorage.getItem('labable_name');
    document.querySelector('#temp').value = localStorage.getItem('labable_temp');
    document.querySelector('#range_temp').value = localStorage.getItem('labable_temp');
    document.querySelector('#channel').value = params.get('ch');
    document.querySelector('#checkinout_checkbox').checked = Boolean(parseInt(localStorage.getItem('labable_checkinout_checkbox'), 10));

    console.log(localStorage.getItem('labable_checkinout_checkbox'));
    document.querySelector('#range_temp').addEventListener('input', function () {
        document.querySelector('#temp').value = this.value;
        saveText(
            {
                id: 'temp',
                value: this.value
            }
        );
    })

    let num_selected = localStorage.getItem('labable_select_how_to_move');
    if (!num_selected) {
        num_selected = 0;
    }
    for (let i = 0; i < document.querySelector('#select_how_to_move').options.length; i++) {

        if (num_selected == i) {
            document.querySelector('#select_how_to_move').options[i].selected = true;
        }
        else {
            document.querySelector('#select_how_to_move').options[i].selected = false;
        }
    }

    if (localStorage.getItem('labable_icon')) {
        document.querySelector('#icon').value = localStorage.getItem('labable_icon');
    }
    if (localStorage.getItem('labable_message')) {
        document.querySelector('#message').value = localStorage.getItem('labable_message');
    }

    getInMembers();
}