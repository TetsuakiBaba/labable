var last_modified = `
last modified: 2023/01/22 01:50:15
`;

var posturl = "";

function showToast(_message) {
    var myToastEl = document.querySelector('#toast');
    var myToast = bootstrap.Toast.getOrCreateInstance(myToastEl)
    document.querySelector('#toast_message').innerHTML = _message;
    myToast.show();
}

function checkinout(dom) {
    if (dom.checked == true) {
        if (!checkin(dom)) {
            dom.checked = false;
        }
    }
    else {
        if (!checkout(dom)) {
            dom.checked = true;
        }
    }

}

// POSTで帰ってきたobjectを渡す
function updateOnsiteUsers(users) {
    document.querySelector('#area_onsite_users').hidden = false;
    // すでにあるspan要素をすべて削除する
    let spans = document.querySelectorAll('#area_onsite_users span');
    for (span of spans) {
        span.remove();
    }

    // オンサイトのユーザ数だけ追加する
    let count = 0;
    for (let user of users) {
        // 自分の名前と等しいのがいる場合はI'm IN nowに変更する
        if (user.name == document.querySelector('#name').value) {
            document.querySelector('#checkinout_checkbox').checked = true;
        }

        let span = document.createElement('span');
        span.classList = "mt-2 mb-2 badge bg-success me-2 position-relative";

        let text_preview = user.text;
        if (user.text) {
            if (text_preview.length > 8) {
                text_preview = text_preview.slice(0, 8);
                text_preview += '...';
            }
        }
        else {
            text_preview = '';
            user.text = '';
        }
        span.innerHTML = user.name + ` <span class="align-bottom" id="clock_${count}"></span>
        <span style="position:absolute;top:-5px;left:-5px;opacity:0.8;font-size:0.6em;" class="badge translate-middle-y rounded-pill bg-light text-secondary">${text_preview}</span>`;
        let time = new Date(user.timestamp * 1000);
        span.addEventListener('click', function () {
            document.querySelector('#modal_name').innerHTML = user.name;
            document.querySelector('#modal_timestamp').innerHTML = time;
            document.querySelector('#modal_message').innerHTML = user.text;
            const modal = new bootstrap.Modal(document.getElementById('modal_user_detail'));
            modal.show();
        })
        document.querySelector('#area_onsite_users').appendChild(span);
        document.querySelector('#area_onsite_users').hidden = false;
        createSVGClock(`#clock_${count}`, { hour: time.getHours(), minute: time.getMinutes(), color: 'white' });
        count++;
    }

}

function checkin(dom) {
    const name = document.querySelector('#name').value;
    if (!name) {
        alert("名前を記入してください");
        return false;
    }

    if (!document.querySelector('#agree_checkbox').checked) {
        alert("注意事項に同意してください");
        return false;
    }


    const icon = document.querySelector('#icon').value;
    const channel = document.querySelector('#channel').value;
    const message = document.querySelector('#message').value;

    const obj = {
        name: name,
        channel: channel,
        icon_emoji: icon,
        text: `${message}`,
        posturl: posturl,
        timestamp: Math.floor(Date.now() / 1000),
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
            if (users.length > 0) {
                updateOnsiteUsers(users);
            }
            else {
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
    const icon = document.querySelector('#icon').value;
    const channel = document.querySelector('#channel').value;
    const message = document.querySelector('#message').value;

    if (!name) {
        alert("名前を記入してください");
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
        text: `${message}`,
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
            if (users.length > 0) {
                updateOnsiteUsers(users);
            }
            else {
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

// サーバからオンサイトの人の一覧を取得する
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
            if (users.length > 0) {
                updateOnsiteUsers(users);
            }
            else {
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
    document.querySelector('#channel').value = params.get('ch');

    if (localStorage.getItem('labable_icon')) {
        document.querySelector('#icon').value = localStorage.getItem('labable_icon');
    }
    if (localStorage.getItem('labable_message')) {
        document.querySelector('#message').value = localStorage.getItem('labable_message');
    }

    document.querySelector('#last_modified').innerText = last_modified;
    getInMembers();
}