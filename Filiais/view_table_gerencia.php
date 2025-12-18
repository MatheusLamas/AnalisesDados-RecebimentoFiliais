<?php
include_once("includes/unidade.php");

$arquivo = "data/Gerencia.csv";

if (!file_exists($arquivo)) {
    die("Erro: O arquivo '$arquivo' não foi encontrado. Por favor, coloque o Gerencia.csv na pasta 'data'.");
}

function normalizar($texto) {
    return strtolower(trim(preg_replace('/\s+/', ' ', $texto)));
}

$registros = [];
$file = fopen($arquivo, "r");
$primeiraLinha = true;
$hoje = new DateTime();

$unidadeFiltro = normalizar($unidadeServico ?? '');

while (($linha = fgetcsv($file, 100000, ";")) !== false) {
    if ($primeiraLinha) { 
        $primeiraLinha = false; 
        continue; 
    }

    $unidade = trim($linha[9] ?? '');

    // Filtro por unidade de serviço
    if ($unidadeFiltro && normalizar($unidade) !== $unidadeFiltro) {
        continue;
    }

    $dataDistribuicao = trim($linha[7] ?? '');
    if (strpos($dataDistribuicao, 'T') !== false) {
        $dataDistribuicao = explode('T', $dataDistribuicao)[0];
    }
    $dataDistribuicao = date("d/m/Y H:i", strtotime($dataDistribuicao));

    $codigoAmostra = trim($linha[0] ?? '');
    $metodo        = trim($linha[3] ?? '');
    $laboratorio   = trim($linha[6] ?? '');
    $unidade       = trim($linha[9] ?? '');
    $numero        = trim($linha[11] ?? '');
    $grupo         = trim($linha[1] ?? '');
    $coleta        = trim($linha[4] ?? '');
    $entrega       = trim($linha[5] ?? '');
    $motivo        = trim($linha[12] ?? '');
    $dataChegada   = trim($linha[12] ?? '');

    $dataObj = DateTime::createFromFormat('d/m/Y H:i', $dataDistribuicao);
    $intervalo = $dataObj ? $dataObj->diff($hoje)->days : 0;
    $status = $dataObj ? (($intervalo > 3 && $hoje >= $dataObj) ? "Atraso" : "No Prazo") : "Data inválida";

    $registros[] = [
        'Codigo'      => $codigoAmostra,
        'Metodo'      => $metodo,
        'Laboratorio' => $laboratorio,
        'Unidade'     => $unidade,
        'Status'      => $status,
        'Data'        => $dataDistribuicao,
        'Intervalo'   => $intervalo,
        'Numero'      => $numero,
        'Grupo'       => $grupo,
        'Coleta'      => $coleta,
        'Entrega'     => $entrega,
        'Motivo'      => $motivo
    ];
}
fclose($file);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Tabela de Registros</title>

<style>
    body { 
        font-family: Segoe UI, Arial, sans-serif; 
        padding: 0; 
        margin: 0;
    }

    #top{
        margin: 10px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    h2 { margin: 0; font-size: 20px; }

    #Button_Excel {
        background-color: #0d6efd;
        color: white;
        border-radius: 4px;
        padding: 6px 14px;
        font-size: 14px;
        border: none;
        cursor: pointer;
        font-weight: 600;
    }
    #Button_Excel:hover {
        background-color: #0b5ed7;
    }

    #Button_Ranking {
        background-color: #28a745;
        color: white;
        border-radius: 4px;
        padding: 6px 14px;
        font-size: 14px;
        border: none;
        cursor: pointer;
        font-weight: 600;
    }
    #Button_Ranking:hover {
        background-color: #1e7e34;
    }

    table {
        border-collapse: collapse; 
        width: 100%; 
        margin-top: 10px;
    }

    thead th {
        position: sticky;
        top: -1px;
        background: #f3f3f3;
        z-index: 30;
        padding: 8px;
        border: 1px solid #ccc;
        font-size: 14px;
        text-align: left;
    }

    th, td { 
        padding: 8px; 
        border: 1px solid #ccc; 
        white-space: nowrap;
        font-size: 14px;
    }

    tbody tr:nth-child(odd) {
        background-color: #eaf4ff;
    }

    .th-wrap {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
    }

    .filter-btn {
        cursor: pointer;
        font-size: 14px;
        padding-left: 6px;
    }

    .filter-box {
        position: absolute;
        background: #ffffff;
        border: 1px solid #b5b5b5;
        border-radius: 4px;
        width: 300px;
        z-index: 9999;
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        overflow: hidden;
        animation: fadeIn 0.10s ease-in-out;
        font-size: 14px;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: scale(0.98); }
        to { opacity: 1; transform: scale(1); }
    }

    .filter-top-buttons {
        display: flex;
        flex-direction: column;
    }

    .filter-box button.action {
        width: 100%;
        padding: 8px 10px;
        border: none;
        border-bottom: 1px solid #e4e4e4;
        background: #ffffff;
        text-align: left;
        font-size: 13.5px;
        cursor: pointer;
        color: #222;
    }
    .filter-box button.action:hover {
        background: #e9f4ff;
    }

    .filter-search {
        padding: 8px 10px;
        border-bottom: 1px solid #e6e6e6;
    }

    .filter-box input[type="text"] {
        width: calc(100% - 12px);
        padding: 6px;
        border: 1px solid #cfcfcf;
        border-radius: 3px;
        font-size: 13px;
    }

    .filter-separator {
        width: 100%;
        height: 1px;
        background: #dcdcdc;
    }

    #filterValues {
        max-height: 300px;
        overflow-y: auto;
        padding: 6px 6px 10px 6px;
        background: white;
    }

    .year-row {
        padding: 6px 6px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        user-select: none;
    }
    .month-row {
        padding: 4px 6px 4px 24px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        user-select: none;
    }
    .day-row {
        padding: 4px 6px 4px 44px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .filter-item:hover {
        background: #f0fbff;
    }

    .select-all {
        padding: 8px 10px;
        display: flex;
        align-items: center;
        gap: 8px;
        border-bottom: 1px solid #e6e6e6;
    }

    .small-checkbox {
        width: 16px;
        height: 16px;
    }

    .toggle-icon {
        width: 14px;
        display: inline-block;
    }

    #pagination { margin: 10px; }
    #pagination button { 
        padding: 6px 12px; 
        margin: 4px; 
        cursor: pointer; 
    }

    #pagination .active { 
        font-weight: bold; 
        background: #ddd; 
    }

</style>
</head>

<body>

<div id="top">
    <h2>Tabela de Registros</h2>
</div>
<div id="top">
    <button id="Button_Excel" onclick="exportarExcel()">Baixar Excel</button>
    <button id="Button_Ranking" onclick="window.location.href = 'includes/table_score.php'">Ranking</button>
</div>

<table>
    <thead>
        <tr>
            <th data-col="Codigo"><div class="th-wrap">Código<span class="filter-btn">🔽</span></div></th>
            <th data-col="Grupo"><div class="th-wrap">Grupo<span class="filter-btn">🔽</span></div></th>
            <th data-col="Metodo"><div class="th-wrap">Metodo<span class="filter-btn">🔽</span></div></th>
            <th data-col="Laboratorio"><div class="th-wrap">Laboratório<span class="filter-btn">🔽</span></div></th>
            <th data-col="Coleta"><div class="th-wrap">Data Coleta<span class="filter-btn">🔽</span></div></th>
            <th data-col="Entrega"><div class="th-wrap">Data Entrega<span class="filter-btn">🔽</span></div></th>
            <th data-col="Unidade"><div class="th-wrap">Unidade Serviço <span class="filter-btn">🔽</span></div></th>
            <th data-col="Data"><div class="th-wrap">Data Distribuição<span class="filter-btn">🔽</span></div></th>
            <th data-col="Intervalo"><div class="th-wrap">Dias na Gerência<span class="filter-btn">🔽</span></div></th>
            <th data-col="Motivo"><div class="th-wrap">Motivo<span class="filter-btn">🔽</span></div></th>
            <th data-col="Status"><div class="th-wrap">Status<span class="filter-btn">🔽</span></div></th>
        </tr>
    </thead>

    <tbody id="tbody"></tbody>
</table>

<div id="pagination"></div>

<div id="infoTabela" style="margin: 10px; font-size: 14px; color: #333;"></div>

<script>
const unidadeServicoAtual = <?php echo json_encode($unidadeServicoAtual); ?>;

function normalizarValor(valor) {
    if (valor === null || valor === undefined) return "";
    let v = valor.toString().trim();
    const m = v.match(reBRDate);
    if (m) {
        return `${m[1]}/${String(parseInt(m[2],10)).padStart(2,'0')}/${m[3]}`;
    }
    return v;
}

const dados = <?php echo json_encode($registros); ?>;
let dadosFiltrados = [...dados];
let filtrosAtivos = {};
let currentPage = 1;
const rowsPerPage = 100;

const urlParams = new URLSearchParams(window.location.search);

let filtroSemanaAtual = urlParams.get('SemanaAtual') === 'true';
let filtroMesAtual    = urlParams.get('MesAtual') === 'true';

if (unidadeServicoAtual && unidadeServicoAtual !== "") {
    filtrosFixosURL["Unidade"] = [unidadeServicoAtual];
    filtrosAtivos["Unidade"]   = [unidadeServicoAtual];
}


const mesesPT = [
  "janeiro","fevereiro","março","abril","maio","junho",
  "julho","agosto","setembro","outubro","novembro","dezembro"
];

const reBRDate = /^\s*([0-3]\d)\/([0-1]\d)\/(20\d{2})(?:\s+\d{1,2}:\d{2}(?::\d{2})?)?\s*$/;

function renderPage() {
    const tbody = document.getElementById("tbody");
    tbody.innerHTML = "";

    const start = (currentPage - 1) * rowsPerPage;
    const end = Math.min(start + rowsPerPage, dadosFiltrados.length);

    for (let i = start; i < end; i++) {
        const r = dadosFiltrados[i];
        const tr = document.createElement("tr");

        tr.innerHTML = `
            <td>${r.Codigo}</td>
            <td>${r.Grupo}</td>
            <td>${r.Metodo}</td>
            <td>${r.Laboratorio}</td>
            <td>${r.Coleta}</td>
            <td>${r.Entrega}</td>
            <td>${r.Unidade}</td>
            <td>${r.Data}</td>
            <td>${r.Intervalo}</td>
            <td>${r.Motivo}</td>
            <td>${r.Status}</td>
        `;
        tbody.appendChild(tr);
    }

    renderPagination();
    atualizarInfoTabela();
    atualizarURL()
}

function renderPagination() {
    const totalPages = Math.max(1, Math.ceil(dadosFiltrados.length / rowsPerPage));
    const container = document.getElementById("pagination");
    container.innerHTML = "";

    const prev = document.createElement("button");
    prev.innerText = "Anterior";
    prev.disabled = currentPage === 1;
    prev.onclick = () => { currentPage--; renderPage(); };
    container.appendChild(prev);

    for (let i = 1; i <= totalPages; i++) {
        const btn = document.createElement("button");
        btn.innerText = i;
        btn.className = (i === currentPage ? "active" : "");
        btn.onclick = () => { currentPage = i; renderPage(); };
        container.appendChild(btn);
    }

    const next = document.createElement("button");
    next.innerText = "Próxima";
    next.disabled = currentPage === totalPages;
    next.onclick = () => { currentPage++; renderPage(); };
    container.appendChild(next);
}

let openDropdown = null;

document.querySelectorAll("th .filter-btn").forEach(btn => {
    btn.addEventListener("click", event => {
        event.stopPropagation();

        const th = btn.closest("th");
        const field = th.dataset.col;

        if (openDropdown) openDropdown.remove();

        const rect = th.getBoundingClientRect();
        const box = document.createElement("div");
        box.className = "filter-box";
        const left = Math.max(6, rect.left + window.scrollX);
        box.style.left = left + "px";
        box.style.top = (rect.bottom + window.scrollY) + "px";
        const dadosBaseFiltro = getDadosParaFiltro(field);

        let rawValores = [
            ...new Set(dadosBaseFiltro.map(r => normalizarValor(r[field])))
        ].filter(v => v !== "");
        const countDates = rawValores.filter(v => reBRDate.test(v)).length;
        const useDateMode = countDates >= 1;

        let dateSortDirection = 'desc';
        let simpleSortDirection = 'asc';

        box.innerHTML = `
            <div class="filter-top-buttons">
                <button class="action" id="btnAsc">Ordenar A → Z</button>
                <button class="action" id="btnDesc">Ordenar Z → A</button>
                <button class="action" id="btnClear">Limpar Filtros</button>
            </div>

            <div class="filter-search">
                <input type="text" placeholder="Pesquisar..." id="filterInput">
                <div class="select-all">
                    <input type="checkbox" id="selectAll" class="small-checkbox">
                    <label for="selectAll">Selecionar tudo</label>
                </div>
            </div>

            <div class="filter-separator"></div>

            <div id="filterValues"></div>
        `;

        document.body.appendChild(box);
        openDropdown = box;
        box.addEventListener("click", e => e.stopPropagation());

        const valuesDiv = box.querySelector("#filterValues");
        const inputSearch = box.querySelector("#filterInput");
        const selectAllCheckbox = box.querySelector("#selectAll");

        function cmpStr(a,b,dir='asc'){ return (dir==='asc')? a.localeCompare(b,'pt-BR',{numeric:true}) : b.localeCompare(a,'pt-BR',{numeric:true}); }
        function cmpDateStr(a,b,dir='asc'){
            const [da,ma,ya] = a.split('/').map(Number);
            const [db,mb,yb] = b.split('/').map(Number);
            const av = new Date(ya, ma-1, da).getTime();
            const bv = new Date(yb, mb-1, db).getTime();
            return (dir==='asc') ? av - bv : bv - av;
        }

        function renderSimpleList(filterTxt = "") {
            valuesDiv.innerHTML = "";
            const valores = rawValores.slice().sort((a,b)=> cmpStr(a,b,simpleSortDirection));
            valores
                .filter(v => v.toLowerCase().includes(filterTxt.toLowerCase()))
                .forEach(v => {
                    const wrap = document.createElement("div");
                    wrap.className = "day-row filter-item";
                    wrap.innerHTML = `
                        <input type="checkbox" class="val-checkbox small-checkbox" value="${escapeHtml(v)}">
                        <label>${escapeHtml(v)}</label>
                    `;
                    valuesDiv.appendChild(wrap);
                });
        }

        function renderDateHierarchy(filterTxt = "") {
            valuesDiv.innerHTML = "";

            const tree = {};
            rawValores.forEach(v => {
                const m = v.match(reBRDate);
                if (!m) return;
                const day = m[1];
                const month = parseInt(m[2],10) - 1;
                const year = m[3];
                const displayDate = `${day}/${String(month+1).padStart(2,'0')}/${year}`;
                tree[year] = tree[year] || {};
                tree[year][month] = tree[year][month] || new Set();
                tree[year][month].add(displayDate);
            });

            let anos = Object.keys(tree).map(Number);
            anos.sort((a,b) => b - a);

            const onlyOneYear = anos.length === 1;

            function makeToggleIcon(collapsed){
                return `<span class="toggle-icon">${collapsed ? '▶' : '▼'}</span>`;
            }

            if (onlyOneYear) {
                const year = String(anos[0]);
                const mesesObj = tree[year];
                let mesesIndices = Object.keys(mesesObj).map(m=>parseInt(m,10));
                mesesIndices.sort((a,b)=> a-b * (dateSortDirection==='asc'?1:1));
                mesesIndices.forEach(mi => {
                    const monthName = mesesPT[mi];
                    const days = Array.from(mesesObj[mi]).sort((a,b)=> cmpDateStr(a,b,'asc'));
                    const monthContainer = document.createElement("div");
                    monthContainer.className = "month-container";

                    const monthHeader = document.createElement("div");
                    monthHeader.className = "month-row";
                    monthHeader.innerHTML = `${makeToggleIcon(false)} <input type="checkbox" class="month-checkbox small-checkbox" data-year="${year}" data-month="${mi}"> <div style="flex:1">${monthName}</div>`;
                    monthContainer.appendChild(monthHeader);

                    const daysContainer = document.createElement("div");
                    daysContainer.className = "days-container";
                    days.forEach(d => {
                        if (filterTxt.trim() !== "" && !d.toLowerCase().includes(filterTxt.toLowerCase())) return;
                        const dayRow = document.createElement("div");
                        dayRow.className = "day-row";
                        dayRow.innerHTML = `<input type="checkbox" class="day-checkbox val-checkbox small-checkbox" data-year="${year}" data-month="${mi}" value="${d}"> <label>${d}</label>`;
                        daysContainer.appendChild(dayRow);
                    });
                    monthContainer.appendChild(daysContainer);
                    valuesDiv.appendChild(monthContainer);
                });

            } else {
                anos.forEach(year => {
                    const mesesObj = tree[String(year)];
                    let mesesIndices = Object.keys(mesesObj).map(m=>parseInt(m,10));
                    mesesIndices.sort((a,b)=> b-a);

                    const yearContainer = document.createElement("div");
                    yearContainer.className = "year-container";

                    const yearHeader = document.createElement("div");
                    yearHeader.className = "year-row";
                    yearHeader.innerHTML = `${makeToggleIcon(false)} <input type="checkbox" class="year-checkbox small-checkbox" data-year="${year}"> <div style="flex:1">${year}</div>`;

                    yearContainer.appendChild(yearHeader);

                    const monthsWrapper = document.createElement("div");
                    monthsWrapper.className = "months-wrapper";

                    mesesIndices.forEach(mi => {
                        const monthName = mesesPT[mi];
                        const days = Array.from(mesesObj[mi]).sort((b,a)=> cmpDateStr(a,b, dateSortDirection));
                        const monthContainer = document.createElement("div");
                        monthContainer.className = "month-container";

                        const monthHeader = document.createElement("div");
                        monthHeader.className = "month-row";
                        monthHeader.innerHTML = `${makeToggleIcon(false)} <input type="checkbox" class="month-checkbox small-checkbox" data-year="${year}" data-month="${mi}"> <div style="flex:1">${monthName}</div>`;
                        monthContainer.appendChild(monthHeader);

                        const daysContainer = document.createElement("div");
                        daysContainer.className = "days-container";
                        days.forEach(d => {
                            if (filterTxt.trim() !== "" && !d.toLowerCase().includes(filterTxt.toLowerCase())) return;
                            const dayRow = document.createElement("div");
                            dayRow.className = "day-row";
                            dayRow.innerHTML = `<input type="checkbox" class="day-checkbox val-checkbox small-checkbox" data-year="${year}" data-month="${mi}" value="${d}"> <label>${d}</label>`;
                            daysContainer.appendChild(dayRow);
                        });

                        monthContainer.appendChild(daysContainer);
                        monthsWrapper.appendChild(monthContainer);
                    });

                    yearContainer.appendChild(monthsWrapper);
                    valuesDiv.appendChild(yearContainer);
                });
            }
        }

        function escapeHtml(text) {
            return text
                .replaceAll('&','&amp;')
                .replaceAll('<','&lt;')
                .replaceAll('>','&gt;')
                .replaceAll('"','&quot;')
                .replaceAll("'","&#039;");
        }

        function unescapeHtml(str) {
            return str.replaceAll('&amp;','&')
                      .replaceAll('&lt;','<')
                      .replaceAll('&gt;','>')
                      .replaceAll('&quot;','"')
                      .replaceAll('&#039;',"'");
        }

        function renderFilteredValues(filterTxt = "") {
            if (useDateMode) {
                rawValores = rawValores.filter(v => reBRDate.test(v));
                renderDateHierarchy(filterTxt);
            } else {
                renderSimpleList(filterTxt);
            }
            attachToggleHandlers();
            attachCheckboxHandlers();
            updateSelectAllState();
        }

        box.querySelector("#btnAsc").onclick = () => {
            ordenarTabela(field, 'asc');
        };

        box.querySelector("#btnDesc").onclick = () => {
            ordenarTabela(field, 'desc');
        };

        box.querySelector("#btnClear").onclick = () => {
            limparTodosOsFiltros();
        };

        inputSearch.addEventListener("input", e => {
            renderFilteredValues(e.target.value);
        });

        selectAllCheckbox.addEventListener("change", () => {
            const checked = selectAllCheckbox.checked;
            valuesDiv.querySelectorAll("input[type='checkbox']").forEach(cb => {
                if (cb.id === 'selectAll') return;
                cb.checked = checked;
            });
            collectSelectionsAndApply(field, useDateMode);
        });

        function ordenarTabela(coluna, direcao = 'asc') {
            const fator = direcao === 'asc' ? 1 : -1;

            dadosFiltrados.sort((a, b) => {
                let va = (a[coluna] ?? "").toString().trim();
                let vb = (b[coluna] ?? "").toString().trim();

                const m1 = va.match(reBRDate);
                const m2 = vb.match(reBRDate);
                if (m1 && m2) {
                    const d1 = parseInt(m1[1], 10);
                    const m1_month = parseInt(m1[2], 10) - 1;
                    const y1 = parseInt(m1[3], 10);
                    const time1 = m1[4] ? m1[4].split(':').map(Number) : [0, 0];
                    const date1 = new Date(y1, m1_month, d1, time1[0], time1[1]);

                    const d2 = parseInt(m2[1], 10);
                    const m2_month = parseInt(m2[2], 10) - 1;
                    const y2 = parseInt(m2[3], 10);
                    const time2 = m2[4] ? m2[4].split(':').map(Number) : [0, 0];
                    const date2 = new Date(y2, m2_month, d2, time2[0], time2[1]);

                    return fator * (date1.getTime() - date2.getTime());
                }

                if (!isNaN(va) && !isNaN(vb)) {
                    return fator * (Number(va) - Number(vb));
                }

                return fator * va.localeCompare(vb, 'pt-BR', { numeric: true });
            });

    currentPage = 1;
    renderPage();
}


        function updateSelectAllState() {
            const boxes = Array.from(valuesDiv.querySelectorAll("input[type='checkbox']")).filter(cb => cb.id !== 'selectAll');
            if (boxes.length === 0) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
                return;
            }
            const all = boxes.every(b => b.checked);
            const some = boxes.some(b => b.checked);
            selectAllCheckbox.checked = all;
            selectAllCheckbox.indeterminate = !all && some;
        }

        function attachToggleHandlers(){
            valuesDiv.querySelectorAll('.year-container').forEach(yearContainer => {
                const header = yearContainer.querySelector('.year-row');
                const monthsWrapper = yearContainer.querySelector('.months-wrapper');
                if (!header || !monthsWrapper) return;
                const icon = header.querySelector('.toggle-icon');
                header.onclick = (e) => {
                    if (e.target.tagName.toLowerCase() === 'input') return;
                    const collapsed = monthsWrapper.style.display === 'none';
                    monthsWrapper.style.display = collapsed ? '' : 'none';
                    if (icon) icon.textContent = collapsed ? '▼' : '▶';
                };
            });
            valuesDiv.querySelectorAll('.month-container').forEach(monthContainer => {
                const header = monthContainer.querySelector('.month-row');
                const daysContainer = monthContainer.querySelector('.days-container');
                if (!header || !daysContainer) return;
                const icon = header.querySelector('.toggle-icon');
                header.onclick = (e) => {
                    if (e.target.tagName.toLowerCase() === 'input') return;
                    const collapsed = daysContainer.style.display === 'none';
                    daysContainer.style.display = collapsed ? '' : 'none';
                    if (icon) icon.textContent = collapsed ? '▼' : '▶';
                };
            });
        }

        function limparTodosOsFiltros() {
            filtrosAtivos = {};
            dadosFiltrados = [...dados];
            currentPage = 1;

            if (openDropdown) {
                openDropdown.remove();
                openDropdown = null;
            }

            renderPage();
        }

        function attachCheckboxHandlers() {
            valuesDiv.querySelectorAll('input.year-checkbox').forEach(ycb => {
                ycb.onchange = () => {
                    const y = ycb.dataset.year;
                    valuesDiv.querySelectorAll(`input[data-year="${y}"]`).forEach(ch => {
                        if (ch !== ycb) ch.checked = ycb.checked;
                    });
                    updateSelectAllState();
                    collectSelectionsAndApply(field, useDateMode);
                };
            });

            valuesDiv.querySelectorAll('input.month-checkbox').forEach(mcb => {
                mcb.onchange = () => {
                    const y = mcb.dataset.year, m = mcb.dataset.month;
                    valuesDiv.querySelectorAll(`input.day-checkbox[data-year="${y}"][data-month="${m}"]`).forEach(ch => ch.checked = mcb.checked);
                    const yearCb = valuesDiv.querySelector(`input.year-checkbox[data-year="${y}"]`);
                    if (yearCb) {
                        const months = valuesDiv.querySelectorAll(`input.month-checkbox[data-year="${y}"]`);
                        yearCb.checked = Array.from(months).every(mm => mm.checked);
                    }
                    updateSelectAllState();
                    collectSelectionsAndApply(field, useDateMode);
                };
            });

            valuesDiv.querySelectorAll('input.day-checkbox').forEach(dcb => {
                dcb.onchange = () => {
                    const y = dcb.dataset.year, m = dcb.dataset.month;
                    const monthCb = valuesDiv.querySelector(`input.month-checkbox[data-year="${y}"][data-month="${m}"]`);
                    if (monthCb) {
                        const days = valuesDiv.querySelectorAll(`input.day-checkbox[data-year="${y}"][data-month="${m}"]`);
                        monthCb.checked = Array.from(days).every(d => d.checked);
                    }
                    const yearCb = valuesDiv.querySelector(`input.year-checkbox[data-year="${y}"]`);
                    if (yearCb) {
                        const months = valuesDiv.querySelectorAll(`input.month-checkbox[data-year="${y}"]`);
                        yearCb.checked = Array.from(months).every(mm => mm.checked);
                    }
                    updateSelectAllState();
                    collectSelectionsAndApply(field, useDateMode);
                };
            });

            valuesDiv.querySelectorAll('input.val-checkbox:not(.day-checkbox)').forEach(cb => {
                cb.onchange = () => {
                    updateSelectAllState();
                    collectSelectionsAndApply(field, useDateMode);
                };
            });
        }

        function getDadosParaFiltro(colunaAtual) {
            return dados.filter(registro => {

                for (const [coluna, valores] of Object.entries(filtrosFixosURL)) {
                    if (coluna === colunaAtual) continue;
                    const valorRegistro = normalizarValor(registro[coluna]);
                    if (!valores.includes(valorRegistro)) return false;
                }

                for (const [coluna, valores] of Object.entries(filtrosAtivos)) {
                    if (coluna === colunaAtual) continue;
                    const valorRegistro = normalizarValor(registro[coluna]);
                    if (!valores.includes(valorRegistro)) return false;
                }

                if (filtroSemanaAtual) {
                    const data = parseDataBR(registro.Recebimento);
                    if (!data) return false;
                    const { inicio, fim } = getInicioFimSemanaAtual();
                    if (data < inicio || data > fim) return false;
                }

                if (filtroMesAtual) {
                    const data = parseDataBR(registro.Recebimento);
                    if (!data) return false;
                    const { inicio, fim } = getInicioFimMesAtual();
                    if (data < inicio || data > fim) return false;
                }

                return true;
            });
        }

        function collectSelectionsAndApply(fieldName, dateMode) {
            if (dateMode) {
                const selectedDays = Array.from(valuesDiv.querySelectorAll("input.day-checkbox:checked")).map(x => x.value);
                Array.from(valuesDiv.querySelectorAll("input.month-checkbox:checked")).forEach(mc => {
                    const y = mc.dataset.year, m = mc.dataset.month;
                    Array.from(valuesDiv.querySelectorAll(`input.day-checkbox[data-year="${y}"][data-month="${m}"]`)).forEach( d => {
                        if (!selectedDays.includes(d.value)) selectedDays.push(d.value);
                    });
                });
                Array.from(valuesDiv.querySelectorAll("input.year-checkbox:checked")).forEach(yc => {
                    const y = yc.dataset.year;
                    Array.from(valuesDiv.querySelectorAll(`input.day-checkbox[data-year="${y}"]`)).forEach(d => {
                        if (!selectedDays.includes(d.value)) selectedDays.push(d.value);
                    });
                });

                if (selectedDays.length === 0) delete filtrosAtivos[fieldName];
                else filtrosAtivos[fieldName] = selectedDays;
            } else {
                const selecionados = Array.from(valuesDiv.querySelectorAll("input.val-checkbox:checked")).map(i => unescapeHtml(i.value));
                if (selecionados.length === 0) delete filtrosAtivos[fieldName];
                else filtrosAtivos[fieldName] = selecionados;
            }

            aplicarFiltrosGerais();
        }

        renderFilteredValues("");

    });
});

document.addEventListener("click", () => {
    if (openDropdown) openDropdown.remove();
    openDropdown = null;
});

function aplicarFiltrosGerais() {
    dadosFiltrados = dados.filter(registro => {

        for (const [coluna, valores] of Object.entries(filtrosFixosURL)) {
            const valorRegistro = normalizarValor(registro[coluna]);
            if (!valores.includes(valorRegistro)) return false;
        }

        for (const [coluna, valores] of Object.entries(filtrosAtivos)) {
            const valorRegistro = normalizarValor(registro[coluna]);
            if (!valores.includes(valorRegistro)) return false;
        }

        if (filtroSemanaAtual) {
            const data = parseDataBR(registro.Recebimento);
            if (!data) return false;

            const { inicio, fim } = getInicioFimSemanaAtual();
            if (data < inicio || data > fim) return false;
        }

        if (filtroMesAtual) {
            const data = parseDataBR(registro.Recebimento);
            if (!data) return false;

            const { inicio, fim } = getInicioFimMesAtual();
            if (data < inicio || data > fim) return false;
        }

        return true;
    });

    currentPage = 1;
    renderPage();
}

function ativarSemanaAtual() {
    filtroSemanaAtual = true;
    filtroMesAtual = false;
    aplicarFiltrosGerais();
}

function ativarMesAtual() {
    filtroMesAtual = true;
    filtroSemanaAtual = false;
    aplicarFiltrosGerais();
}

function limparFiltrosDePeriodo() {
    filtroSemanaAtual = false;
    filtroMesAtual = false;
    aplicarFiltrosGerais();
}


function parseDataBR(valor) {
    if (!valor) return null;

    const dataStr = valor.toString().split(' ')[0];

    const partes = dataStr.split('/');
    if (partes.length !== 3) return null;

    return new Date(
        parseInt(partes[2], 10),
        parseInt(partes[1], 10) - 1,
        parseInt(partes[0], 10)
    );
}


function getInicioFimSemanaAtual() {
    const hoje = new Date();
    const diaSemana = hoje.getDay();
    const inicio = new Date(hoje);
    inicio.setDate(hoje.getDate() - diaSemana);
    inicio.setHours(0,0,0,0);

    const fim = new Date(inicio);
    fim.setDate(inicio.getDate() + 6);
    fim.setHours(23,59,59,999);

    return { inicio, fim };
}

function getInicioFimMesAtual() {
    const hoje = new Date();
    const inicio = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
    const fim = new Date(hoje.getFullYear(), hoje.getMonth() + 1, 0, 23,59,59,999);
    return { inicio, fim };
}


function getParamsFromURL() {
    const params = new URLSearchParams(window.location.search);
    const obj = {};
    for (const [key, value] of params.entries()) {
        obj[key] = value;
    }
    return obj;
}

function aplicarFiltrosDaURL() {
    const params = getParamsFromURL();

    filtrosAtivos = {};
    filtrosFixosURL = {};

    filtroSemanaAtual = false;
    filtroMesAtual = false;

    Object.entries(params).forEach(([key, value]) => {

        if (key === "SemanaAtual" && value === "true") {
            filtroSemanaAtual = true;
            filtroMesAtual = false;
            return;
        }

        if (key === "MesAtual" && value === "true") {
            filtroMesAtual = true;
            filtroSemanaAtual = false;
            return;
        }

        if (key === "page") {
            currentPage = parseInt(value, 10) || 1;
            return;
        }

        if (key === "rows") {
            rowsPerPage = parseInt(value, 10) || rowsPerPage;
            return;
        }

        const valores = value.split("|");
        filtrosAtivos[key] = valores;
        filtrosFixosURL[key] = valores;
    });

    aplicarFiltrosGerais();
}

function atualizarURL() {
    const params = new URLSearchParams();

    params.set("page", currentPage);
    params.set("rows", rowsPerPage);

    if (filtroMesAtual) params.set("MesAtual", "true");
    if (filtroSemanaAtual) params.set("SemanaAtual", "true");

    Object.entries(filtrosAtivos).forEach(([coluna, valores]) => {
        if (valores.length > 0) {
            params.set(coluna, valores.join("|"));
        }
    });

    const novaURL = `${window.location.pathname}?${params.toString()}`;
    window.history.replaceState({}, "", novaURL);
}



function exportarExcel() {
    let csv = "\uFEFFCódigo;Grupo;Método;Laboratório;Coleta;Entrega;Unidade;Data;Intervalo;Motivo;Status\n";

    dadosFiltrados.forEach(r => {
        csv += `${r.Codigo};${r.Grupo};${r.Metodo};${r.Laboratorio};${r.Coleta};${r.Entrega};${r.Unidade};${r.Data};${r.Intervalo};${r.Motivo};${r.Status}\n`;
    });

    const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
    const url = URL.createObjectURL(blob);

    const link = document.createElement("a");
    link.href = url;
    link.download = "tabela.csv";
    link.click();
}

function atualizarInfoTabela() {
    const total = dadosFiltrados.length;

    if (total === 0) {
        document.getElementById("infoTabela").innerText =
            "Mostrando 0 registros";
        return;
    }

    const inicio = (currentPage - 1) * rowsPerPage + 1;
    const fim = Math.min(currentPage * rowsPerPage, total);

    document.getElementById("infoTabela").innerText =
        `Mostrando ${inicio} a ${fim} de ${total} registros`;
}

aplicarFiltrosDaURL();

</script>

</body>
</html>
