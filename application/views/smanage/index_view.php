<ul class="tabs" data-tabs id="tablist">
    <li class="tabs-title is-active"><a href="#panel2-1" aria-selected="true">System</a></li>
    <li class="tabs-title"><a href="#panel2-2">Providers</a></li>
</ul>

<div class="tabs-content" data-tabs-content="tablist">
    <section class="tabs-panel is-active" id="panel2-1">
        <table class="details">
            <tbody>
            <tr>
                <td><?php echo lang('rr_ormvalidate') . ', PHP version'; ?></td>
                <td>
                    <button id="vormversion" class="button"><?php echo lang('rr_runprocess'); ?></button>
                </td>
            </tr>
            <tr id="rvormversion" class="hidden">
                <td colspan="2"></td>
            </tr>
            <tr>
                <td><?php echo lang('rr_validatedbschema'); ?></td>
                <td>
                    <button id="vschema" class="button"><?php echo lang('rr_runprocess'); ?></button>
                </td>
            </tr>
            <tr id="rvschema" class="hidden">
                <td colspan="2"></td>
            </tr>
            <tr>
                <td><?php echo lang('rr_validatedbsync'); ?></td>
                <td>
                    <button id="vschemadb" class="button"><?php echo lang('rr_runprocess'); ?></button>
                </td>
            </tr>
            <tr id="rvschemadb" class="hidden">
                <td colspan="2"></td>
            </tr>
            <tr>
                <td><?php echo lang('rr_runmigration'); ?></td>
                <td>
                    <button id="vmigrate" class="button"><?php echo lang('rr_runprocess'); ?></button>
                </td>
            </tr>
            <tr id="rvmigrate" class="hidden">
                <td colspan="2"></td>
            </tr>
            </tbody>
        </table>
    </section>
    <section class="tabs-panel" id="panel2-2">
        <table class="details">
            <tbody>
            <tr>
                <td>Certificates checks</td>
                <td><div>
                        <select>
                            <optgroup label="Expired certificates">
                                <option value="localidp|expired">IdP (local)</option>
                                <option value="localsp|expired">SP (local)</option>
                                <option value="extidp|expired">IdP (external)</option>
                                <option value="extsp|expired">SP (external)</option>
                            </optgroup>
                            <optgroup label="Missing signing certificates">
                                <option value="localidp|missingsigning">IdP (local)</option>
                                <option value="localsp|missingsigning">SP (local)</option>
                                <option value="extidp|missingsigning">IdP (external)</option>
                                <option value="extsp|missingsigning">SP (external)</option>
                            </optgroup>
                            <optgroup label="Missing encryption certificates">
                                <option value="localidp|missingencryption">IdP (local)</option>
                                <option value="localsp|missingencryption">SP (local)</option>
                                <option value="extidp|missingencryption">IdP (external)</option>
                                <option value="extsp|missingencryption">SP (external)</option>
                            </optgroup>
                        </select>
                        <button id="vcerts" class="button" value=""><?php echo lang('rr_runprocess'); ?></button></div></td>
            </tr>
            <tr id="rvcerts" class="hidden">
                <td colspan="2"></td>
            </tr>
            </tbody>
        </table>
    </section>
</div>
