from __future__ import annotations

import base64
import hashlib
import hmac
from typing import Any

from cryptography.hazmat.primitives import padding
from cryptography.hazmat.primitives.ciphers import Cipher, algorithms, modes


def meituan_build_authorization(
    *, method: str, uri: str, date: str, client_id: str, client_secret: str
) -> str:
    string_to_sign = f"{method} {uri}\n{date}"
    sig = base64.b64encode(
        hmac.new(client_secret.encode("utf-8"), string_to_sign.encode("utf-8"), hashlib.sha1).digest()
    ).decode("ascii")
    return f"MWS {client_id}:{sig}"


def xc_decode_bytes(text: Any) -> bytes | None:
    if not isinstance(text, str) or not text or (len(text) % 2) != 0:
        return None
    out = bytearray()
    a = ord("a")
    for i in range(0, len(text), 2):
        high = (ord(text[i]) - a) & 0xF
        low = (ord(text[i + 1]) - a) & 0xF
        out.append((high << 4) | low)
    return bytes(out)


def xc_encode_bytes(data: bytes) -> str:
    out = []
    a = ord("a")
    for b in data:
        out.append(chr(((b >> 4) & 0xF) + a))
        out.append(chr((b & 0xF) + a))
    return "".join(out)


def _xc_encrypt_bytes(plaintext: bytes, *, key: bytes, iv: bytes) -> bytes:
    padder = padding.PKCS7(128).padder()
    padded = padder.update(plaintext) + padder.finalize()
    cipher = Cipher(algorithms.AES(key), modes.CBC(iv))
    encryptor = cipher.encryptor()
    return encryptor.update(padded) + encryptor.finalize()


def _xc_decrypt_bytes(ciphertext: bytes, *, key: bytes, iv: bytes) -> bytes | None:
    try:
        cipher = Cipher(algorithms.AES(key), modes.CBC(iv))
        decryptor = cipher.decryptor()
        padded = decryptor.update(ciphertext) + decryptor.finalize()
        unpadder = padding.PKCS7(128).unpadder()
        return unpadder.update(padded) + unpadder.finalize()
    except Exception:
        return None


def xc_encrypt(plaintext: str, *, aes_key: str, aes_iv: str) -> str:
    ct = _xc_encrypt_bytes(plaintext.encode("utf-8"), key=aes_key.encode("utf-8"), iv=aes_iv.encode("utf-8"))
    return xc_encode_bytes(ct)


def xc_decrypt(body_str: str, *, aes_key: str, aes_iv: str) -> str | None:
    raw = xc_decode_bytes(body_str)
    if raw is None:
        return None
    pt = _xc_decrypt_bytes(raw, key=aes_key.encode("utf-8"), iv=aes_iv.encode("utf-8"))
    if pt is None:
        return None
    try:
        return pt.decode("utf-8")
    except Exception:
        return None


def xc_sign_target(
    *,
    account_id: str,
    service_name: str,
    request_time: str,
    body_str: str,
    version: str,
    sign_key: str,
) -> str:
    s = f"{account_id}{service_name}{request_time}{body_str}{version}{sign_key}"
    return hashlib.md5(s.encode("utf-8")).hexdigest().lower()

