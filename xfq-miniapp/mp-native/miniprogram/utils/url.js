function getOriginFromBaseUrl(baseUrl) {
  const base = String(baseUrl || '').trim()
  const match = base.match(/^(https?:\/\/[^/]+)/)
  return match ? match[1] : ''
}

function normalizeNetworkUrl(src, baseUrl) {
  const s = String(src || '').trim()
  if (!s) return ''
  if (/^https?:\/\//.test(s)) return s
  if (s.startsWith('//')) return `https:${s}`

  const origin = getOriginFromBaseUrl(baseUrl)
  if (!origin) return s

  if (s.startsWith('/')) return origin + s
  return `${origin}/${s}`
}

function normalizeRichTextHtml(html, baseUrl) {
  if (!html) return ''
  const origin = getOriginFromBaseUrl(baseUrl)

  let content = String(html)

  content = content.replace(/<img/gi, '<img style="max-width: 100%; height: auto;"')

  if (origin) {
    content = content.replace(
      /(<img[^>]*\bsrc=['"])(?!https?:\/\/)([^'">]+)(['"][^>]*>)/gi,
      (match, prefix, url, suffix) => `${prefix}${normalizeNetworkUrl(url, baseUrl)}${suffix}`
    )
  }

  return content
}

module.exports = {
  getOriginFromBaseUrl,
  normalizeNetworkUrl,
  normalizeRichTextHtml,
}

