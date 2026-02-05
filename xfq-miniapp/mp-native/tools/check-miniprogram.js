/* eslint-disable no-console */
const fs = require('fs')
const path = require('path')
const vm = require('vm')

function readJson(filePath) {
  const raw = fs.readFileSync(filePath, 'utf8')
  return JSON.parse(raw)
}

function exists(filePath) {
  try {
    fs.accessSync(filePath, fs.constants.F_OK)
    return true
  } catch (e) {
    return false
  }
}

function walkFiles(dirPath, predicate, out = []) {
  const entries = fs.readdirSync(dirPath, { withFileTypes: true })
  for (const entry of entries) {
    const full = path.join(dirPath, entry.name)
    if (entry.isDirectory()) {
      walkFiles(full, predicate, out)
      continue
    }
    if (!entry.isFile()) continue
    if (!predicate || predicate(full)) out.push(full)
  }
  return out
}

function normalizePosix(p) {
  return String(p || '').replace(/\\/g, '/')
}

function collectPageRoutes(appJson) {
  const routes = []

  const mainPages = Array.isArray(appJson.pages) ? appJson.pages : []
  for (const p of mainPages) routes.push(normalizePosix(p))

  const subs = Array.isArray(appJson.subpackages)
    ? appJson.subpackages
    : Array.isArray(appJson.subPackages)
      ? appJson.subPackages
      : []

  for (const sp of subs) {
    if (!sp || typeof sp !== 'object') continue
    const root = normalizePosix(sp.root)
    const spPages = Array.isArray(sp.pages) ? sp.pages : []
    for (const p of spPages) routes.push(path.posix.join(root, normalizePosix(p)))
  }

  return routes
}

function checkPageFiles(miniprogramRoot, pageRoutes) {
  const errors = []
  const warnings = []

  for (const route of pageRoutes) {
    const base = path.join(miniprogramRoot, route)

    const required = ['.js', '.json', '.wxml']
    for (const ext of required) {
      const fp = base + ext
      if (!exists(fp)) errors.push(`[missing] ${normalizePosix(route + ext)}`)
    }

    const wxss = base + '.wxss'
    if (!exists(wxss)) warnings.push(`[missing-optional] ${normalizePosix(route + '.wxss')}`)

    const jsonPath = base + '.json'
    if (exists(jsonPath)) {
      try {
        readJson(jsonPath)
      } catch (e) {
        errors.push(`[invalid-json] ${normalizePosix(route + '.json')}: ${String(e && e.message ? e.message : e)}`)
      }
    }
  }

  return { errors, warnings }
}

function checkJsonFiles(jsonFiles) {
  const errors = []
  for (const fp of jsonFiles) {
    try {
      readJson(fp)
    } catch (e) {
      errors.push(`[invalid-json] ${normalizePosix(fp)}: ${String(e && e.message ? e.message : e)}`)
    }
  }
  return errors
}

function checkJsSyntax(jsFiles) {
  const errors = []
  for (const fp of jsFiles) {
    try {
      const code = fs.readFileSync(fp, 'utf8')
      new vm.Script(code, { filename: fp, displayErrors: true })
    } catch (e) {
      errors.push(`[invalid-js] ${normalizePosix(fp)}: ${String(e && e.message ? e.message : e)}`)
    }
  }
  return errors
}

function main() {
  const projectRoot = path.resolve(__dirname, '..')
  const miniprogramRoot = path.resolve(projectRoot, 'miniprogram')
  const appJsonPath = path.join(miniprogramRoot, 'app.json')

  if (!exists(appJsonPath)) {
    console.error(`FATAL: missing ${normalizePosix(path.relative(projectRoot, appJsonPath))}`)
    process.exit(2)
  }

  let appJson
  try {
    appJson = readJson(appJsonPath)
  } catch (e) {
    console.error(`FATAL: invalid app.json: ${String(e && e.message ? e.message : e)}`)
    process.exit(2)
  }

  const pageRoutes = collectPageRoutes(appJson)

  const { errors: pageErrors, warnings: pageWarnings } = checkPageFiles(miniprogramRoot, pageRoutes)

  const jsonFiles = walkFiles(miniprogramRoot, (fp) => fp.endsWith('.json'))
  const jsonErrors = checkJsonFiles(jsonFiles)

  const jsFiles = walkFiles(miniprogramRoot, (fp) => fp.endsWith('.js'))
  const jsErrors = checkJsSyntax(jsFiles)

  const errors = [...pageErrors, ...jsonErrors, ...jsErrors]
  const warnings = [...pageWarnings]

  if (warnings.length) {
    console.log(`WARNINGS (${warnings.length})`)
    for (const w of warnings) console.log(`- ${w}`)
  }

  if (errors.length) {
    console.log(`ERRORS (${errors.length})`)
    for (const err of errors) console.log(`- ${err}`)
    process.exit(1)
  }

  console.log(
    `OK: pages=${pageRoutes.length}, js=${jsFiles.length}, json=${jsonFiles.length}, warnings=${warnings.length}`
  )
}

main()

