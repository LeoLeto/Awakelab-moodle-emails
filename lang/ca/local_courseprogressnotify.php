<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Notificacions de progrés de curs';

// Scheduled task names.
$string['task_check_progress_25'] = 'Notificació de progrés 25%';
$string['task_check_progress_50'] = 'Notificació de progrés 50%';
$string['task_check_course_end_soon'] = 'Avís: 7 dies abans de finalitzar el curs';
$string['task_check_course_last_day'] = 'Avís: darrer dia del curs';
$string['task_check_zoom_sessions'] = 'Recordatoris de sessions Zoom';
$string['task_check_presential_sessions'] = 'Recordatoris de sessions presencials';
$string['task_check_diploma_available'] = 'Avís: diploma disponible (30 dies)';

// Manual run page.
$string['runpage:title'] = 'Verificació manual del progrés';
$string['runpage:heading'] = 'Verificar progrés ara';
$string['runpage:desc'] = 'Pots executar manualment diferents verificacions de notificacions. Selecciona el tipus que vols provar:';
$string['runpage:type_progress'] = 'Notificacions de Progrés (25% i 50%)';
$string['runpage:confirm_progress'] = 'Revisar tots els estudiants de la categoria configurada i enviar correus als que hagin assolit el 25% o 50% de progrés (si no han estat notificats prèviament).';
$string['runpage:type_courseend'] = 'Notificacions de Fi de Curs (7 dies abans i últim dia)';
$string['runpage:confirm_courseend'] = 'Revisar cursos que finalitzen aviat (exactament dins de 7 dies) o avui, i enviar recordatoris als estudiants matriculats (si no han estat notificats prèviament).';
$string['runpage:type_zoom'] = 'Notificacions de Sessions Zoom';
$string['runpage:confirm_zoom'] = 'Revisar sessions Zoom properes (segons dies configurats) i enviar recordatoris als estudiants matriculats (si no han estat notificats prèviament).';
$string['runpage:confirm'] = 'Fes clic al botó per iniciar la verificació. Es revisaran tots els estudiants de la categoria configurada i s\'enviaran correus als que hagin assolit el 25% o 50% de progrés (si no han estat notificats prèviament).';
$string['run_progress_button'] = 'Provar Correus de Progrés';
$string['run_courseend_button'] = 'Provar Correus de Fi de Curs';
$string['run_zoom_button'] = 'Provar Correus de Zoom';
$string['backtosettings'] = 'Tornar a configuració';
$string['run_now_button'] = 'Verificar progrés ara';
$string['run_now_done'] = 'La verificació s\'ha executat correctament.';
$string['run_now_error'] = 'S\'ha produït un error durant l\'execució:';
$string['runpage:nocategory'] = 'No hi ha cap categoria seleccionada a la configuració. Selecciona\'n una per habilitar les operacions del connector.';

// Settings.
$string['settings:category'] = 'Categoria objectiu';
$string['settings:category_desc'] = 'Selecciona la categoria on s’aplicarà aquest connector. Si no se’n selecciona cap ("Cap"), el connector no realitzarà cap operació.';
$string['settings:category:none'] = 'Cap (deshabilitat)';
$string['settings:zoomdaysbefore'] = 'Dies abans per a la invitació Zoom';
$string['settings:zoomdaysbefore_desc'] = 'Nombre de dies abans de la data de la sessió Zoom per enviar la invitació automàtica.';
$string['settings:presentialdaysbefore'] = 'Dies abans per a sessions presencials';
$string['settings:presentialdaysbefore_desc'] = 'Nombre de dies abans de la sessió presencial (examen/tutoria) per enviar el recordatori.';

// Run block in settings page.
$string['settings:run:desc'] = 'Executa manualment la verificació del progrés des d’aquesta pàgina. Només s’aplicarà a la categoria seleccionada.';

// Progress table.
$string['progress:header:activity'] = 'Activitat';
$string['progress:header:status'] = 'Estat';
$string['progress:status:complete'] = 'Completat';
$string['progress:status:incomplete'] = 'Pendent';

// Privacy.
$string['privacy:metadata'] = 'El connector local_courseprogressnotify emmagatzema registres de notificacions enviades als usuaris.';
$string['privacy:metadata:local_courseprogressnotify_log'] = 'Registre de notificacions enviades';
$string['privacy:metadata:local_courseprogressnotify_log:userid'] = 'Usuari destinatari';
$string['privacy:metadata:local_courseprogressnotify_log:courseid'] = 'Curs associat a la notificació';
$string['privacy:metadata:local_courseprogressnotify_log:notification_type'] = 'Tipus de notificació enviada';
$string['privacy:metadata:local_courseprogressnotify_log:entityid'] = 'Identificador de l\'entitat associada (p. ex., Zoom o sessió)';
$string['privacy:metadata:local_courseprogressnotify_log:time_sent'] = 'Marca temporal de l\'enviament';

// Email templates (from emails_ca.txt).
$string['email_zoom_subject'] = 'Sessió Zoom del curs {{coursename}}';
$string['email_zoom_body'] = '<p>Hola {{firstname}}!</p>

<p>T\'escrivim en relació al curs <strong>{{coursename}}</strong> que estàs realitzant.</p>

<p>Et recordem que el proper <strong>{{zoom_date}}</strong>, de <strong>{{zoom_start}}</strong> a <strong>{{zoom_end}}</strong>, tindrà lloc una sessió en directe per Zoom amb el/la tutor/a.</p>

<p>El mateix dia de la sessió, quan accedeixis a la plataforma, veuràs l\'enllaç per unir-te directament a la videotrucada.</p>

<p>Aquesta sessió té com a objectiu:</p>
<ul>
  <li>Resoldre dubtes del curs</li>
  <li>Profunditzar en continguts específics</li>
  <li>Fer una sessió més dinàmica i pràctica</li>
</ul>

<p>T’esperem!</p>';

$string['email_25_subject'] = 'Seguiment del 25% del curs {{coursename}}';
$string['email_25_body'] = '<p>Benvingut/da {{firstname}},</p>

<p>T\'informem que has assolit el <strong>25%</strong> del curs <strong>{{coursename}}</strong>. Aquesta és la teva evolució fins avui:</p>

{{progress_table}}

<p>Encara hi ha temps per finalitzar el curs, que conclou el <strong>{{courseenddate}}</strong>. Recorda que per completar-lo cal:</p>

<ul>
  <li>Arribar a una connexió mínima del 75% de les hores</li>
  <li>Visualitzar el 100% dels continguts</li>
  <li>Realitzar les activitats d\'avaluació</li>
</ul>

<p>Recorda també completar el <strong>Qüestionari de valoració de l\'alumne</strong>, disponible a l\'apartat d\'avaluació de la qualitat.</p>

<p>Per a qualsevol dubte, contacta amb nosaltres.</p>

<p>Salutacions,</p>';

$string['email_50_subject'] = 'Seguiment meitat del curs {{coursename}}';
$string['email_50_body'] = '<p>Benvingut/da {{firstname}}!</p>

<p>Ja hem arribat a la meitat del curs <strong>{{coursename}}</strong>. El curs finalitza el <strong>{{courseenddate}}</strong> i per completar-lo cal visualitzar tots els continguts i realitzar les activitats.</p>

<p>Aquesta és la teva evolució fins avui:</p>

{{progress_table}}

<p>T\'animem a continuar avançant i a contactar amb nosaltres davant qualsevol dubte.</p>

<p>Una salutació,</p>';

$string['email_end_soon_subject'] = 'Recta final del curs {{coursename}}';
$string['email_end_soon_body'] = '<p>Hola {{firstname}}!</p>

<p>Hem entrat a l\'última setmana del curs <strong>{{coursename}}</strong>, que finalitza el proper <strong>{{courseenddate}}</strong>.</p>

<p>Recorda els requisits de finalització:</p>

<ul>
  <li>Completar les avaluacions</li>
  <li>Visualitzar el 100% dels continguts</li>
  <li>Arribar al 75% de connexió</li>
</ul>

<p>Aprofita aquests darrers dies per finalitzar el curs.</p>

<p>Molts ànims!</p>';

$string['email_last_day_subject'] = 'Instruccions de finalització del curs {{coursename}}';
$string['email_last_day_body'] = '<p>Hola {{firstname}},</p>

<p>Et recordem que demà és l\'últim dia de formació del curs <strong>{{coursename}}</strong>.</p>

<p>Ha estat un plaer comptar amb tu i esperem que l\'experiència hagi estat profitosa.</p>

<p>Si encara no ho has fet, completa les avaluacions i verifica que has visualitzat tots els continguts per obtenir el diploma.</p>

<p>També pots realitzar el qüestionari de satisfacció final.</p>

<p>Un cop finalitzat el curs, ens posarem en contacte per informar-te de la disponibilitat del diploma.</p>

<p>Gràcies per la teva participació.</p>

<p>Una cordial salutació,</p>';

$string['email_exam_subject'] = 'Examen presencial obligatori del curs {{coursename}}';
$string['email_exam_body'] = '<p>Hola {{firstname}},</p>

<p>Et recordem que el proper <strong>{{exam_date}}</strong> està programat l\'examen presencial obligatori del curs <strong>{{coursename}}</strong>.</p>

<p><strong>Lloc:</strong> {{exam_location}}<br>
<strong>Horari:</strong> de {{exam_start}} a {{exam_end}}</p>

<p>És imprescindible haver completat totes les activitats i avaluacions per poder accedir a l\'examen.</p>

<p>Recorda arribar amb antelació i portar el DNI o NIE.</p>

<p>Molta sort!</p>';

$string['email_tutoring_subject'] = 'Tutoria presencial obligatòria del curs {{coursename}}';
$string['email_tutoring_body'] = '<p>Hola {{firstname}},</p>

<p>El proper <strong>{{tutoring_date}}</strong> tindrà lloc la tutoria presencial obligatòria del curs <strong>{{coursename}}</strong>.</p>

<p><strong>Lloc:</strong> {{tutoring_location}}<br>
<strong>Horari:</strong> de {{tutoring_start}} a {{tutoring_end}}</p>

<p>Durant la tutoria es realitzaran activitats pràctiques i resolució de dubtes.</p>

<p>Et recomanem portar preparades les teves consultes.</p>

<p>Ens veiem aviat!</p>';

$string['email_diploma_subject'] = 'Diploma del curs {{coursename}}';
$string['email_diploma_body'] = '<p>Benvolgut/da {{firstname}},</p>

<p>Des d\'avui ja tens disponible al campus virtual el diploma del curs <strong>{{coursename}}</strong>.</p>

<p>Pots accedir al campus des del següent enllaç:</p>

<p><a href="{{campus_url}}">{{campus_url}}</a></p>

<p>Per descarregar el diploma cal haver completat tots els requisits del curs.</p>

<p>Si encara no ho has fet, recorda completar el qüestionari de satisfacció final.</p>

<p>Moltes gràcies per la teva participació.</p>

<p>Una salutació,</p>';
