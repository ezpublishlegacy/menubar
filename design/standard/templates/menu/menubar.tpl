{* Menubar Template *}
{if $menubar.has_items}
<ul class="{$menubar.class}">
	{foreach $menubar.items as $item}
	<li{if $item.class} class="{$item.class}"{/if}>
		{if $item.has_link}<a href="{$item.link}"{if $item.is_external} class="external" rel="external" target="_blank"{/if}>{else}<span class="inactive">{/if}{$item.content}{if $item.has_link}</a>{else}</span>{/if}{if $item.delimiter}<span class="delimiter">{$item.delimiter}<span>{/if}
		{if and(is_set($item.has_menu), $item.has_menu)}{menubar($item.menu)}{/if}
		{if $item.has_menu_display}{include uri="design:menu/menu_display.tpl" items=$item.menu_display count=count($item.menu_display)}{/if}
	</li>
	{/foreach}
</ul>
{/if}