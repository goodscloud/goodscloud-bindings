from urllib import urlencode
from urllib2 import urlopen
from base64 import encodestring
from hashlib import sha1
import hmac
from pprint import pprint
from base64 import b64encode
import time
import json
import md5


API_SERVER = "http://test.goodscloud.net"
API_SERVER = "http://localhost:5000"


def login(email, password):
    data = (('email', email), ('password', password),)
    session = json.loads(urlopen(API_SERVER + '/session', urlencode(data)).read())
    assert session['email'] == email, "Login failed"
    return session


def signed_request(path, method, params, auth, post_data=None):
    # the request is valid for 10 seconds
    expires = time.strftime('%Y-%m-%dT%H:%M:%SZ',
                            time.gmtime(time.time() + 10))
    params += [('key', auth['app_key']), ('token', auth['app_token']),
               ('expires', expires), ]
    # parameters are sorted, but not urlencoded, for md5 digest
    str_params = '&'.join(("%s=%s" % (a, b) for a, b in sorted(params)))

    sign_str = '\n'.join([
        method,
        path,
        md5.new(str_params).hexdigest(),
        md5.new(post_data or '').hexdigest(),
        auth['app_token'],
        expires
    ])

    sign = b64encode(hmac.new(str(auth['app_secret']),
                              sign_str.encode('utf-8'),
                              sha1).digest()).rstrip('=')
    params += [('sign', sign)]

    url = API_SERVER + path + '?' + urlencode(params)
    return urlopen(url, post_data)


if __name__ == "__main__":
    # log in to get session data
    email = "user1@example.org"
    password = "secret"
    session = login(email, password)

    # search for products with this GTIN
    gtin = "00000000"
    query = json.dumps({'filters': [{"name": "gtin", "op": "eq", "val": gtin}]})
    result = signed_request('/api/internal/company_product',
                            'GET',
                            [('q', query),],
                            session['auth']
                            ).read()
    data = json.loads(result)
    pprint(data)
