@extends('legal.layout')

@section('title', 'Informativa sulla privacy')

@section('content')
    <h1>Informativa sulla privacy</h1>
    <p class="updated">Ultimo aggiornamento: 17 giugno 2026</p>

    <p>
        La presente informativa descrive come <strong>Replisa</strong> tratta i dati personali
        nell'ambito del servizio di automazione della messaggistica WhatsApp per piccole e medie
        imprese erogato tramite la WhatsApp Business Platform (Meta Cloud API), ai sensi del
        Regolamento (UE) 2016/679 (&laquo;GDPR&raquo;).
    </p>

    <h2>1. Titolare del trattamento</h2>
    <p>
        Titolare del trattamento è <strong>Giovanni Melfi</strong> (marchio &laquo;Replisa&raquo; /
        theMelfix), libero professionista, P.IVA IT01809180886, con sede in
        Vittoria (RG).<br>
        Per qualunque richiesta relativa ai tuoi dati puoi scrivere a
        <a href="mailto:info@giovannimelfi.it">info@giovannimelfi.it</a>.
    </p>

    <h2>2. Doppio ruolo di Replisa</h2>
    <p>Replisa tratta dati personali in due ruoli distinti:</p>
    <ul>
        <li>
            <strong>Come Titolare</strong> — per i dati delle imprese clienti che sottoscrivono il
            servizio (dati dell'account, di contatto e di fatturazione).
        </li>
        <li>
            <strong>Come Responsabile del trattamento</strong> — per i dati dei contatti finali
            (i destinatari dei messaggi WhatsApp) trattati per conto dell'impresa cliente, che ne
            resta Titolare. In questo caso le finalità e i mezzi del trattamento sono determinati
            dall'impresa cliente, sulla base di un accordo ai sensi dell'art. 28 GDPR.
        </li>
    </ul>

    <h2>3. Tipologie di dati trattati</h2>
    <table>
        <tr><th>Categoria</th><th>Dati</th><th>Ruolo Replisa</th></tr>
        <tr>
            <td>Account cliente</td>
            <td>Nome, email, nome dell'attività, credenziali, dati di fatturazione</td>
            <td>Titolare</td>
        </tr>
        <tr>
            <td>Contatti finali</td>
            <td>Numero di telefono, nome profilo WhatsApp, contenuto e metadati dei messaggi, stato di consegna, stato di opt-in</td>
            <td>Responsabile</td>
        </tr>
        <tr>
            <td>Dati tecnici</td>
            <td>Log applicativi, indirizzo IP, dati di utilizzo del servizio</td>
            <td>Titolare</td>
        </tr>
    </table>

    <h2>4. Finalità e basi giuridiche</h2>
    <ul>
        <li><strong>Erogazione del servizio</strong> (invio/ricezione messaggi, automazioni) — esecuzione del contratto (art. 6.1.b GDPR).</li>
        <li><strong>Messaggi promozionali / richieste di recensione</strong> verso i contatti finali — consenso (opt-in) raccolto dall'impresa cliente (art. 6.1.a GDPR).</li>
        <li><strong>Adempimenti fiscali e contabili</strong> — obbligo di legge (art. 6.1.c GDPR).</li>
        <li><strong>Sicurezza e prevenzione abusi</strong> — legittimo interesse (art. 6.1.f GDPR).</li>
    </ul>

    <h2>5. Modalità del trattamento e destinatari</h2>
    <p>
        I dati sono trattati con strumenti elettronici e ospitati su server situati nell'Unione
        Europea (VPS IONOS). Per l'invio e la ricezione dei messaggi Replisa si avvale della
        <strong>WhatsApp Business Platform di Meta Platforms Ireland Ltd.</strong>, che agisce come
        ulteriore responsabile/destinatario. Il trattamento da parte di Meta è regolato dalle
        relative condizioni e informative; eventuali trasferimenti verso paesi terzi avvengono
        sulla base delle Clausole Contrattuali Standard approvate dalla Commissione Europea.
    </p>

    <h2>6. Conservazione dei dati</h2>
    <p>
        I dati dell'account sono conservati per la durata del rapporto contrattuale e, dove
        richiesto, per i termini di legge (es. 10 anni per i documenti fiscali). I dati dei
        contatti finali sono conservati secondo le istruzioni dell'impresa cliente e cancellati
        al termine del servizio o su sua richiesta.
    </p>

    <h2>7. Diritti dell'interessato</h2>
    <p>
        Hai diritto di accesso, rettifica, cancellazione, limitazione, portabilità e opposizione
        (artt. 15-22 GDPR), oltre al diritto di revocare il consenso in qualsiasi momento. Se sei
        un contatto finale, queste richieste vanno di norma rivolte all'impresa che ti ha
        contattato (Titolare); Replisa la assisterà come Responsabile. Puoi inoltre proporre
        reclamo al Garante per la protezione dei dati personali (<a href="https://www.garanteprivacy.it">garanteprivacy.it</a>).
    </p>

    <h2>8. Cookie</h2>
    <p>
        Il sito utilizza esclusivamente cookie tecnici necessari al funzionamento. Eventuali
        cookie di terze parti o di misurazione saranno gestiti tramite apposito banner di consenso.
    </p>

    <h2>9. Modifiche</h2>
    <p>
        Replisa può aggiornare la presente informativa. La versione vigente è sempre pubblicata su
        questa pagina con la relativa data di aggiornamento.
    </p>
@endsection
