YUI.add("moodle-availability_coursecompleted-form",function(e,t){M.availability_coursecompleted=M.availability_coursecompleted||{},M.availability_coursecompleted.form=e.Object(M.core_availability.plugin),M.availability_coursecompleted.form.completed=null,M.availability_coursecompleted.form.initInner=function(e){this.completed=e},M.availability_coursecompleted.form.getNode=function(t){var n=M.util.get_string("title","availability_coursecompleted"),r='<label class="form-group"><span class="p-r-1">'+n+"</span>";r+='<span class="availability-coursecompleted"><select class="custom-select" name="id" title='+n+">",r+='<option value="choose">'+M.util.get_string("choosedots","moodle")+"</option>",r+='<option value="1">'+M.util.get_string("yes","moodle")+"</option>",r+='<option value="0">'+M.util.get_string("no","moodle")+"</option>",r+="</select></span></label>";var i=e.Node.create('<span class="form-inline">'+r+"</span>");t.creating===undefined&&(t.id!==undefined&&i.one("select[name=id] > option[value="+t.id+"]")?i.one("select[name=id]").set("value",""+t.id):t.id===undefined&&i.one("select[name=id]").set("value","choose"));if(!M.availability_coursecompleted.form.addedEvents){M.availability_coursecompleted.form.addedEvents=!0;var s=e.one(".availability-field");s.delegate("change",function(){M.core_availability.form.update()},".availability_coursecompleted select")}return i},M.availability_coursecompleted.form.fillValue=function(e,t){var n=t.one("select[name=id]").get("value");n==="choose"?e.id="":e.id=n},M.availability_coursecompleted.form.fillErrors=function(e,t){var n=t.one("select[name=id]").get("value");n==="choose"&&e.push("availability_coursecompleted:missing")}},"@VERSION@",{requires:["base","node","event","moodle-core_availability-form"]});