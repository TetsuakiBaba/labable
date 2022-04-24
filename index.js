
var posturl = "";

function showToast(_message) {
    var myToastEl = document.querySelector('#toast');
    var myToast = bootstrap.Toast.getOrCreateInstance(myToastEl)
    document.querySelector('#toast_message').innerHTML = _message;
    myToast.show();
}
function checkin(dom) {
    const name = document.querySelector('#name').value;
    const temp = document.querySelector('#temp').value;

    if (!name || !temp) {
        alert("名前、体温を記入してください");
        return;
    }

    if (!document.querySelector('#agree_checkbox').checked) {
        alert("注意事項に同意してください");
        return;
    }


    const icon = document.querySelector('#icon').value;
    const channel = document.querySelector('#channel').value;
    const message = document.querySelector('#message').value;
    const how_to_move = document.querySelector('#select_how_to_move').options[document.querySelector('#select_how_to_move').selectedIndex].innerHTML;

    if (37.5 <= temp) {
        alert("無理をせずお家で休みましょう");
        return;
    }

    const obj = {
        name: name,
        channel: channel,
        icon_emoji: icon,
        text: `*IN @ ${temp} [${how_to_move}]* ${message}`,
        posturl: posturl
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
        })
        .catch(console.error);

    dom.disabled = true;
    setTimeout(function () {
        dom.disabled = false;
    }, 1000);
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
    if (!document.querySelector('#agree_checkbox').checked) {
        alert("注意事項に同意してください");
        return;
    }

    const name = document.querySelector('#name').value;
    const temp = document.querySelector('#temp').value;
    const icon = document.querySelector('#icon').value;
    const channel = document.querySelector('#channel').value;
    const message = document.querySelector('#message').value;
    const how_to_move = document.querySelector('#select_how_to_move').options[document.querySelector('#select_how_to_move').selectedIndex].innerHTML;

    const obj = {
        name: name,
        channel: channel,
        icon_emoji: icon,
        text: `*OUT [${how_to_move}]* ${message}`,
        posturl: posturl
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
        })
        .catch(console.error);

    dom.disabled = true;
    setTimeout(function () {
        dom.disabled = false;
    }, 1000);
}

function saveText(obj) {
    localStorage.setItem(`labable_${obj.id}`, obj.value);
}
window.onload = function () {

    // URLを取得
    let url = new URL(window.location.href);

    // URLSearchParamsオブジェクトを取得
    let params = url.searchParams;

    // getメソッド
    if (!params.get('posturl') || !params.get('ch')) {
        alert('Invalid URL');
        window.location.href = "./getStart.html";
        return;
    }
    posturl = params.get('posturl');
    console.log(posturl);

    document.querySelector('#name').value = localStorage.getItem('labable_name');
    document.querySelector('#temp').value = localStorage.getItem('labable_temp');
    document.querySelector('#channel').value = params.get('ch');

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

}