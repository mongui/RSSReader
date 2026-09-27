<?php if (!defined('MVCious')) exit('No direct script access allowed');
/**
 * Spanish translation.
 *
 * Keys: the English text as it's written in the views and scripts.
 * 'context|text' keys are used when the same text needs different translations.
 */
return array(
	// Main page.
	'Loading...'							=> 'Cargando...',
	'Preferences'							=> 'Preferencias',
	'Import feeds'							=> 'Importar feeds',
	'Logout'								=> 'Cerrar sesión',
	'Add Feed'								=> 'Añadir feed',
	'Feed URL:'								=> 'URL del feed:',
	'Paste here the feed URL'				=> 'Pega aquí la URL del feed',
	'Add feed'								=> 'Añadir feed',
	'Highlights'							=> 'Destacados',
	'Unreaded'								=> 'No leídos',
	'Starred'								=> 'Favoritos',
	'Last readed'							=> 'Últimos leídos',
	'Search'								=> 'Buscar',
	'Search terms'							=> 'Términos de búsqueda',
	'Subscriptions'							=> 'Suscripciones',
	'Mark feed as read'						=> 'Marcar feed como leído',
	'Change feed name'						=> 'Cambiar nombre del feed',
	'Add to a folder'						=> 'Añadir a una carpeta',
	'Update feed'							=> 'Actualizar feed',
	'Unsubscribe'							=> 'Cancelar suscripción',
	'What is RSS Reader?'					=> '¿Qué es RSS Reader?',
	'RSS logo'								=> 'Logo de RSS',
	'RSS Reader is an aggregator. Thanks to the aggregators or web feed readers (programs or sites that let you read web sources) summaries of all the sites you want can be obtained from the desktop of the operating system, e-mail programs or through web applications that function as aggregators. There is no need to open the browser and visit dozens of pages.'
											=> 'RSS Reader es un agregador. Gracias a los agregadores o lectores de feeds (programas o sitios que permiten leer fuentes web) se pueden obtener resúmenes de todos los sitios que quieras desde el escritorio del sistema operativo, desde programas de correo o a través de aplicaciones web que funcionan como agregadores. No hace falta abrir el navegador y visitar decenas de páginas.',
	'What is RSS?'							=> '¿Qué es RSS?',
	'RSS stands for Really Simple Syndication, an XML format for sharing content on the Web. It\'s used to spread frequently updated information to users who have subscribed to the content source. The format allows to distribute content without a browser, using software designed to read these RSS feeds (aggregator). Nevertheless, it is possible to use a browser to read RSS content. The latest versions of the major browsers can read RSS with no additional software required. RSS is part of the family of XML formats developed specifically for all types of sites that are updated frequently and through which information can be shared and used on other web sites or programs. This is known as web or web syndication.'
											=> 'RSS son las siglas de Really Simple Syndication, un formato XML para compartir contenido en la web. Se usa para difundir información que se actualiza con frecuencia a los usuarios que se han suscrito a la fuente de contenido. El formato permite distribuir contenido sin un navegador, usando programas diseñados para leer estos feeds RSS (agregadores). Aun así, también se puede usar un navegador para leer contenido RSS: las últimas versiones de los principales navegadores pueden leer RSS sin necesidad de programas adicionales. RSS forma parte de la familia de formatos XML desarrollados específicamente para todo tipo de sitios que se actualizan con frecuencia y mediante los cuales se puede compartir información y usarla en otros sitios web o programas. Esto se conoce como sindicación web.',
	'Last update:'							=> 'Última actualización:',
	'by'									=> 'por',
	'post|Unreaded'							=> 'No leído',
	'post|Starred'							=> 'Favorito',
	'No name'								=> 'Sin nombre',

	// Feed list menu.
	'Please enter the new feed name:'		=> 'Introduce el nuevo nombre del feed:',
	'New folder name:'						=> 'Nombre de la nueva carpeta:',
	'New folder'							=> 'Nueva carpeta',
	'Are you sure you want to unsubscribe from this feed?'
											=> '¿Seguro que quieres cancelar la suscripción a este feed?',
	'The feed was successfully added.'		=> 'El feed se ha añadido correctamente.',

	// Preferences.
	'Your preferences'						=> 'Tus preferencias',
	'Your email'							=> 'Tu email',
	'Display time format'					=> 'Formato de fecha',
	'Display language'						=> 'Idioma',
	'English'								=> 'Inglés',
	'Spanish'								=> 'Español',
	'Change password'						=> 'Cambiar contraseña',
	'Current password'						=> 'Contraseña actual',
	'New password'							=> 'Nueva contraseña',
	'Repeat new password'					=> 'Repite la nueva contraseña',
	'Server configuration'					=> 'Configuración del servidor',
	'Timezone of the server'				=> 'Zona horaria del servidor',
	'Minutes between feeds updates'			=> 'Minutos entre actualizaciones de feeds',
	'Max. feeds per update'					=> 'Máx. feeds por actualización',
	'Show favicons in the feedlist'			=> 'Mostrar favicons en la lista de feeds',
	'Users can update feeds'				=> 'Los usuarios pueden actualizar feeds',
	'Yes'									=> 'Sí',
	'Update preferences'					=> 'Guardar preferencias',
	'Your password must be at least 6 characters.'
											=> 'La contraseña debe tener al menos 6 caracteres.',
	'Passwords do not match.'				=> 'Las contraseñas no coinciden.',
	'We need your current password to verify your identity.'
											=> 'Necesitamos tu contraseña actual para verificar tu identidad.',
	'Data saved.'							=> 'Datos guardados.',
	'Your current password is not correct.'	=> 'Tu contraseña actual no es correcta.',
	'Something wrong happened. We can\'t save your preferences now. Sorry.'
											=> 'Algo ha ido mal. No podemos guardar tus preferencias ahora. Lo sentimos.',

	// Global feed list (admin).
	'Global feed list'						=> 'Lista global de feeds',
	'Feed name'								=> 'Nombre del feed',
	'Go to the feed'						=> 'Ir al feed',
	'Delete feed'							=> 'Borrar feed',
	'Modify feed'							=> 'Modificar feed',
	'Feed ID:'								=> 'ID del feed:',
	'Feed name:'							=> 'Nombre del feed:',
	'Site main URL:'						=> 'URL principal del sitio:',
	'RSS Feed URL:'							=> 'URL del feed RSS:',
	'Favicon URL:'							=> 'URL del favicon:',
	'Active'								=> 'Activo',
	'Cancel'								=> 'Cancelar',
	'Update'								=> 'Actualizar',
	'The feed was successfully modified.'	=> 'El feed se ha modificado correctamente.',
	'The feed couldn\'t be modified.'		=> 'No se ha podido modificar el feed.',
	'Delete the feed "%s" with all its posts for every user?'
											=> '¿Borrar el feed "%s" con todos sus posts para todos los usuarios?',
	'This can\'t be undone.'				=> 'Esto no se puede deshacer.',
	'The feed was successfully deleted.'	=> 'El feed se ha borrado correctamente.',
	'The feed couldn\'t be deleted.'		=> 'No se ha podido borrar el feed.',

	// Import OPML.
	'Name:'									=> 'Nombre:',
	'Browse'								=> 'Examinar',
	'Import OPML'							=> 'Importar OPML',
	'Select a file first.'					=> 'Selecciona primero un archivo.',
	'File successfully uploaded.'			=> 'Archivo subido correctamente.',
	'The file you tried to upload is not compatible.'
											=> 'El archivo que has intentado subir no es compatible.',
	'Something wrong happened. We can\'t upload your file now. Sorry.'
											=> 'Algo ha ido mal. No podemos subir tu archivo ahora. Lo sentimos.',

	// Common.
	'Can\'t reach the server. Please, try again later.'
											=> 'No se puede conectar con el servidor. Por favor, inténtalo más tarde.',
);
